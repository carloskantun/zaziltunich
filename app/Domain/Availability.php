<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database as DB;

/** Disponibilidad: horarios por fecha, capacidad compartida, bloqueos y consumo por reservas. */
final class Availability
{
    /** Estados que ocupan cupo (pendiente/retenida solo mientras no expire). */
    public static function countedSql(): string
    {
        return "(b.status IN ('deposit_paid','paid') OR (b.status IN ('pending','hold') AND (b.hold_expires_at IS NULL OR b.hold_expires_at > ?)))";
    }

    private static function nowStr(): string
    {
        return now_site()->format('Y-m-d H:i:s');
    }

    /** Si la fecha está dentro de la ventana de venta (anticipación mínima y máxima). */
    public static function dateBookable(array $exp, string $date): bool
    {
        $today = today_site();
        if ($date < $today || $date > date('Y-m-d', strtotime($today . ' +' . $exp['max_advance_days'] . ' days'))) {
            return false;
        }
        return true;
    }

    private static function timeBookable(array $exp, string $date, ?string $time): bool
    {
        $at = new \DateTimeImmutable($date . ' ' . ($time ?: '23:59') . ':00', site_tz());
        return $at >= now_site()->modify('+' . $exp['lead_hours'] . ' hours');
    }

    private static function blocked(array $exp, string $date, ?string $time): bool
    {
        $rows = DB::all(
            'SELECT time FROM blocks WHERE date_from <= ? AND date_to >= ? AND (experience_id = ? OR (capacity_group_id IS NOT NULL AND capacity_group_id = ?))',
            [$date, $date, $exp['id'], $exp['capacity_group_id'] ?? 0]
        );
        foreach ($rows as $r) {
            if ($r['time'] === null || $r['time'] === '' || $r['time'] === $time) {
                return true;
            }
        }
        return false;
    }

    /** Ids de experiencias que comparten la capacidad con $exp. */
    private static function scopeIds(array $exp): array
    {
        if (!empty($exp['capacity_group_id'])) {
            return array_map('intval', array_column(DB::all('SELECT id FROM experiences WHERE capacity_group_id = ?', [$exp['capacity_group_id']]), 'id'));
        }
        return [$exp['id']];
    }

    /** Personas (o unidades) ya reservadas para fecha/hora, compartiendo capacidad. */
    public static function booked(array $exp, string $date, ?string $time, ?int $excludeBookingId = null): int
    {
        $ids = self::scopeIds($exp);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $col = $exp['kind'] === 'slot' ? 'pax' : 'units';
        $sql = "SELECT COALESCE(SUM(b.$col),0) FROM bookings b WHERE b.experience_id IN ($in) AND b.date = ? AND " . self::countedSql();
        $params = [...$ids, $date, self::nowStr()];
        if ($time !== null) {
            $sql .= ' AND b.time = ?';
            $params[] = $time;
        }
        if ($excludeBookingId) {
            $sql .= ' AND b.id <> ?';
            $params[] = $excludeBookingId;
        }
        return (int) DB::value($sql, $params);
    }

    /** Horarios (slot/evento) de una fecha con cupo restante. @return array<int,array{time:string,capacity:int,booked:int,left:int}> */
    public static function slots(array $exp, string $date): array
    {
        if (!self::dateBookable($exp, $date) || $exp['kind'] === 'lodging' || $exp['kind'] === 'quote') {
            return [];
        }
        $dow = (string) date('N', strtotime($date . ' 12:00:00'));
        $times = [];
        if ($exp['kind'] === 'event') {
            foreach ($exp['event_dates'] as $ev) {
                if ($ev['event_date'] === $date) {
                    $times[$ev['time']] = max($times[$ev['time']] ?? 0, (int) $ev['capacity']);
                }
            }
        } else {
            foreach ($exp['schedules'] as $s) {
                if (!in_array($dow, explode(',', (string) $s['weekdays']), true)) {
                    continue;
                }
                if (($s['valid_from'] && $date < $s['valid_from']) || ($s['valid_to'] && $date > $s['valid_to'])) {
                    continue;
                }
                $cap = $s['capacity'] !== null ? (int) $s['capacity'] : $exp['default_capacity'];
                if (!empty($exp['capacity_group_id'])) {
                    $cap = (int) DB::value('SELECT capacity FROM capacity_groups WHERE id = ?', [$exp['capacity_group_id']]);
                }
                foreach ($s['times'] as $t) {
                    $times[$t] = max($times[$t] ?? 0, $cap);
                }
            }
        }
        ksort($times);
        $out = [];
        foreach ($times as $time => $cap) {
            if (self::blocked($exp, $date, $time) || !self::timeBookable($exp, $date, $time)) {
                continue;
            }
            $booked = self::booked($exp, $date, $time);
            $out[] = ['time' => $time, 'capacity' => $cap, 'booked' => $booked, 'left' => max(0, $cap - $booked)];
        }
        return $out;
    }

    /** Días del mes con alguna disponibilidad. @return array<string,bool> */
    public static function month(array $exp, string $ym): array
    {
        $days = [];
        $first = $ym . '-01';
        $count = (int) date('t', strtotime($first));
        for ($d = 1; $d <= $count; $d++) {
            $date = sprintf('%s-%02d', $ym, $d);
            if ($exp['kind'] === 'lodging') {
                $days[$date] = self::lodgingNightFree($exp, $date);
            } elseif ($exp['kind'] === 'quote') {
                $days[$date] = false;
            } else {
                $ok = false;
                foreach (self::slots($exp, $date) as $s) {
                    if ($s['left'] > 0) {
                        $ok = true;
                        break;
                    }
                }
                $days[$date] = $ok;
            }
        }
        return $days;
    }

    /** Hospedaje: ¿hay alguna unidad libre esa noche? */
    public static function lodgingNightFree(array $exp, string $night, int $units = 1, ?int $exclude = null): bool
    {
        if (!self::dateBookable($exp, $night) || self::blocked($exp, $night, null)) {
            return false;
        }
        return self::lodgingBooked($exp, $night, $exclude) + $units <= $exp['default_capacity'];
    }

    public static function lodgingBooked(array $exp, string $night, ?int $exclude = null): int
    {
        $ids = self::scopeIds($exp);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT COALESCE(SUM(b.units),0) FROM bookings b WHERE b.experience_id IN ($in) AND b.date <= ? AND b.end_date > ? AND " . self::countedSql();
        $params = [...$ids, $night, $night, self::nowStr()];
        if ($exclude) {
            $sql .= ' AND b.id <> ?';
            $params[] = $exclude;
        }
        return (int) DB::value($sql, $params);
    }

    /**
     * Verifica que una selección cabe. Devuelve null si está bien o un código de error.
     */
    public static function check(array $exp, array $sel, ?int $excludeBookingId = null): ?string
    {
        $date = (string) $sel['date'];
        $pax = max(1, (int) ($sel['pax'] ?? 1));
        $units = max(1, (int) ($sel['units'] ?? 1));
        if ($exp['kind'] === 'quote') {
            return 'err.generic';
        }
        if ($exp['kind'] === 'lodging') {
            $end = (string) ($sel['end_date'] ?? '');
            $n = Pricing::nightsBetween($date, $end);
            if ($n < 1) {
                return 'err.generic';
            }
            for ($i = 0; $i < $n; $i++) {
                $night = date('Y-m-d', strtotime($date . " +$i days"));
                if (!self::lodgingNightFree($exp, $night, $units, $excludeBookingId)) {
                    return 'err.slot_full';
                }
            }
            return null;
        }
        $time = (string) ($sel['time'] ?? '');
        foreach (self::slots($exp, $date) as $s) {
            if ($s['time'] === $time) {
                $need = $exp['kind'] === 'slot' ? $pax : $units;
                $left = $s['capacity'] - self::booked($exp, $date, $time, $excludeBookingId);
                return $need <= $left ? null : 'err.slot_full';
            }
        }
        return 'err.past';
    }
}
