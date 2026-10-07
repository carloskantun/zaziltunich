<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Auth, Database as DB, View};
use App\Domain\{BookingService, Catalog, Selection};

final class BookingsController extends Base
{
    private const PER_PAGE = 40;

    public function index(): void
    {
        BookingService::expireHolds();
        $where = ['1=1'];
        $params = [];
        $status = (string) ($_GET['status'] ?? '');
        if ($status !== '' && in_array($status, BookingService::STATUSES, true)) {
            $where[] = 'b.status = ?';
            $params[] = $status;
        }
        $exp = (int) ($_GET['exp'] ?? 0);
        if ($exp > 0) {
            $where[] = 'b.experience_id = ?';
            $params[] = $exp;
        }
        foreach (['from' => '>=', 'to' => '<='] as $k => $op) {
            $v = (string) ($_GET[$k] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                $where[] = "b.date $op ?";
                $params[] = $v;
            }
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(b.code LIKE ? OR c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $w = implode(' AND ', $where);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = (int) DB::value("SELECT COUNT(*) FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE $w", $params);
        $rows = DB::all(
            "SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, t.title
             FROM bookings b JOIN customers c ON c.id = b.customer_id
             LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
             WHERE $w ORDER BY b.date DESC, b.time DESC, b.id DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        );
        $counts = [];
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM bookings GROUP BY status') as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        $this->page('bookings', [
            'title' => 'Reservas', 'rows' => $rows, 'counts' => $counts, 'total' => $total, 'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)), 'filters' => compact('status', 'exp', 'q') + ['from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? ''],
            'experiences' => $this->expOptions(),
        ]);
    }

    private function expOptions(): array
    {
        return DB::all("SELECT e.id, t.title FROM experiences e LEFT JOIN experience_translations t ON t.experience_id = e.id AND t.lang = 'es' ORDER BY e.sort_order, e.id");
    }

    public function show(array $p): void
    {
        $b = BookingService::find((int) $p['id']);
        if (!$b) {
            View::notFound();
        }
        $this->page('booking', [
            'title' => $b['code'], 'b' => $b, 'exp' => Catalog::load((int) $b['experience_id']),
            'extras' => BookingService::extras($b['id']), 'payments' => BookingService::payments($b['id']),
        ]);
    }

    public function status(array $p): void
    {
        $this->guard();
        try {
            BookingService::setStatus((int) $p['id'], (string) ($_POST['status'] ?? ''));
            $this->back('admin/reservas/' . (int) $p['id'], 'Estado actualizado.');
        } catch (\InvalidArgumentException) {
            $this->back('admin/reservas/' . (int) $p['id'], 'Estado inválido.', 'err');
        }
    }

    public function payment(array $p): void
    {
        $user = $this->guard();
        $id = (int) $p['id'];
        $amount = to_cents($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $this->back('admin/reservas/' . $id, 'Indica un monto mayor a cero.', 'err');
        }
        BookingService::addPayment($id, $amount, 'manual', trim((string) ($_POST['method'] ?? '')), trim((string) ($_POST['reference'] ?? '')), (int) $user['id']);
        $this->back('admin/reservas/' . $id, 'Pago registrado.');
    }

    public function notes(array $p): void
    {
        $this->guard();
        DB::update('bookings', ['notes' => trim((string) ($_POST['notes'] ?? '')) ?: null, 'updated_at' => now_site()->format('Y-m-d H:i:s')], 'id = ?', [(int) $p['id']]);
        $this->back('admin/reservas/' . (int) $p['id'], 'Notas guardadas.');
    }

    /** Reserva manual (teléfono, WhatsApp, mostrador): paso 1 elegir experiencia, paso 2 widget. */
    public function create(): void
    {
        $expId = (int) ($_GET['exp'] ?? 0);
        $exp = $expId ? Catalog::load($expId) : null;
        $this->page('booking_new', [
            'scripts' => $exp ? '<script src="' . e(asset('js/booking.js')) . '" defer></script>' : '',
            'title' => 'Nueva reserva', 'exp' => $exp && $exp['status'] === 'published' ? $exp : null,
            'experiences' => DB::all("SELECT e.id, e.kind, t.title FROM experiences e LEFT JOIN experience_translations t ON t.experience_id = e.id AND t.lang = 'es' WHERE e.status = 'published' ORDER BY e.sort_order, e.id"),
        ], ['admin', 'operator']);
    }

    public function store(): void
    {
        $this->guard();
        $exp = Catalog::load((int) ($_POST['exp'] ?? 0));
        if (!$exp) {
            View::notFound();
        }
        $sel = Selection::fromInput($_POST);
        $source = in_array($_POST['source'] ?? '', ['phone', 'whatsapp', 'walkin', 'email'], true) ? (string) $_POST['source'] : 'phone';
        $r = BookingService::create(
            $exp, $sel,
            ['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '', 'phone' => $_POST['phone'] ?? ''],
            $source, in_array($_POST['lang'] ?? '', ['es', 'en'], true) ? $_POST['lang'] : 'es',
            trim((string) ($_POST['notes'] ?? '')) ?: null
        );
        if (!$r['ok']) {
            $msg = implode(' ', array_map(static fn ($e) => t($e['code'], $e['vars'] ?? []), $r['errors']));
            $this->back('admin/reservas/nueva?exp=' . $exp['id'], $msg, 'err');
        }
        $this->back('admin/reservas/' . $r['booking']['id'], 'Reserva creada: ' . $r['booking']['code']);
    }
}
