<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database as DB;

/** Creación y ciclo de vida de reservas. Todo servidor-autoritativo y dentro de transacción. */
final class BookingService
{
    public const STATUSES = ['hold', 'pending', 'deposit_paid', 'paid', 'cancelled', 'refunded', 'no_show', 'expired'];

    /**
     * @return array{ok:bool, booking?:array, errors?:array}
     */
    public static function create(array $exp, array $sel, array $customer, string $source = 'web', string $lang = 'es', ?string $notes = null, ?string $status = null): array
    {
        $name = trim((string) ($customer['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($customer['email'] ?? '')));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'errors' => [['code' => 'err.required']]];
        }
        try {
            return DB::tx(function () use ($exp, $sel, $customer, $source, $lang, $notes, $name, $email, $status) {
                DB::lockRow('experiences', $exp['id']);
                if (!empty($exp['capacity_group_id'])) {
                    DB::lockRow('capacity_groups', (int) $exp['capacity_group_id']);
                }
                $q = Pricing::quote($exp, $sel);
                if (!$q['ok']) {
                    return ['ok' => false, 'errors' => $q['errors']];
                }
                if ($source === 'web' || empty($sel['skip_availability'])) {
                    $err = Availability::check($exp, $sel);
                    if ($err !== null) {
                        return ['ok' => false, 'errors' => [['code' => $err]]];
                    }
                }
                $cust = DB::one('SELECT * FROM customers WHERE email = ?', [$email]);
                $now = now_site()->format('Y-m-d H:i:s');
                $custId = $cust ? (int) $cust['id'] : DB::insert('customers', [
                    'name' => $name, 'email' => $email, 'phone' => trim((string) ($customer['phone'] ?? '')),
                    'lang' => $lang, 'created_at' => $now,
                ]);
                $holdMin = (int) Settings::get('hold_minutes', '1440');
                $expires = $holdMin > 0 ? now_site()->modify("+$holdMin minutes")->format('Y-m-d H:i:s') : null;
                $date = (string) $sel['date'];
                $time = isset($sel['time']) && $sel['time'] !== '' ? (string) $sel['time'] : null;
                $balanceDue = $q['deposit'] < $q['total'] ? date('Y-m-d', strtotime($date . ' -1 day')) : null;
                $id = DB::insert('bookings', [
                    'code' => self::newCode(),
                    'experience_id' => $exp['id'],
                    'variant_id' => !empty($sel['variant_id']) ? (int) $sel['variant_id'] : null,
                    'customer_id' => $custId,
                    'date' => $date,
                    'time' => $time,
                    'end_date' => $exp['kind'] === 'lodging' ? (string) $sel['end_date'] : null,
                    'nights' => $q['nights'],
                    'pax' => max(1, (int) ($sel['pax'] ?? 1)),
                    'units' => max(1, (int) ($sel['units'] ?? 1)),
                    'currency' => $exp['currency'],
                    'base_total' => from_cents($q['base']),
                    'extras_total' => from_cents($q['extras_total']),
                    'total' => from_cents($q['total']),
                    'deposit_due' => from_cents($q['deposit']),
                    'paid_amount' => '0.00',
                    'status' => $status ?? 'pending',
                    'source' => $source,
                    'lang' => $lang,
                    'notes' => $notes,
                    'pricing_snapshot' => json_encode(['lines' => $q['lines'], 'unit_price' => $q['unit_price']], JSON_UNESCAPED_UNICODE),
                    'hold_expires_at' => $source === 'web' ? $expires : null,
                    'balance_due_date' => $balanceDue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                foreach ($q['extras'] as $x) {
                    DB::insert('booking_extras', [
                        'booking_id' => $id, 'group_id' => $x['group_id'], 'option_id' => $x['option_id'],
                        'label' => $x['label'], 'qty' => $x['qty'], 'amount' => from_cents($x['amount']),
                    ]);
                }
                return ['ok' => true, 'booking' => self::find($id)];
            });
        } catch (\Throwable $e) {
            error_log('[booking] ' . $e->getMessage());
            return ['ok' => false, 'errors' => [['code' => 'err.generic']]];
        }
    }

    public static function newCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = 'ZT-';
            for ($i = 0; $i < 7; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (DB::value('SELECT 1 FROM bookings WHERE code = ?', [$code]));
        return $code;
    }

    public static function find(int $id): ?array
    {
        return self::hydrate(DB::one(
            'SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
             FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE b.id = ?',
            [$id]
        ));
    }

    public static function findByCode(string $code): ?array
    {
        return self::hydrate(DB::one(
            'SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
             FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE b.code = ?',
            [strtoupper(trim($code))]
        ));
    }

    public static function hydrate(?array $b): ?array
    {
        if (!$b) {
            return null;
        }
        foreach (['base_total', 'extras_total', 'total', 'deposit_due', 'paid_amount'] as $k) {
            $b[$k] = to_cents($b[$k]);
        }
        $b['balance'] = max(0, $b['total'] - $b['paid_amount']);
        return $b;
    }

    public static function extras(int $bookingId): array
    {
        return array_map(static function ($r) {
            $r['amount'] = to_cents($r['amount']);
            return $r;
        }, DB::all('SELECT * FROM booking_extras WHERE booking_id = ? ORDER BY id', [$bookingId]));
    }

    public static function payments(int $bookingId): array
    {
        return array_map(static function ($r) {
            $r['amount'] = to_cents($r['amount']);
            return $r;
        }, DB::all('SELECT * FROM payments WHERE booking_id = ? ORDER BY id', [$bookingId]));
    }

    /** Registra un pago exitoso y recalcula el estado. */
    public static function addPayment(int $bookingId, int $amountCents, string $gateway, string $method = '', string $reference = '', ?int $userId = null): void
    {
        DB::tx(function () use ($bookingId, $amountCents, $gateway, $method, $reference, $userId) {
            $b = self::find($bookingId);
            if (!$b || $amountCents <= 0) {
                throw new \InvalidArgumentException('Pago inválido');
            }
            $kind = ($b['paid_amount'] + $amountCents >= $b['total']) ? 'balance' : 'deposit';
            DB::insert('payments', [
                'booking_id' => $bookingId, 'gateway' => $gateway, 'method' => $method, 'reference' => $reference,
                'kind' => $b['paid_amount'] === 0 && $amountCents >= $b['total'] ? 'full' : $kind,
                'amount' => from_cents($amountCents), 'currency' => $b['currency'], 'status' => 'succeeded',
                'created_by' => $userId, 'created_at' => now_site()->format('Y-m-d H:i:s'),
            ]);
            $paid = $b['paid_amount'] + $amountCents;
            $status = $paid >= $b['total'] ? 'paid' : 'deposit_paid';
            DB::update('bookings', [
                'paid_amount' => from_cents($paid), 'status' => $status, 'hold_expires_at' => null,
                'updated_at' => now_site()->format('Y-m-d H:i:s'),
            ], 'id = ?', [$bookingId]);
        });
    }

    public static function setStatus(int $bookingId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Estado inválido');
        }
        DB::update('bookings', ['status' => $status, 'updated_at' => now_site()->format('Y-m-d H:i:s')], 'id = ?', [$bookingId]);
    }

    /** Marca como expiradas las retenciones vencidas. */
    public static function expireHolds(): int
    {
        return DB::exec(
            "UPDATE bookings SET status = 'expired' WHERE status IN ('pending','hold') AND hold_expires_at IS NOT NULL AND hold_expires_at <= ?",
            [now_site()->format('Y-m-d H:i:s')]
        );
    }
}
