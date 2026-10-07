<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database as DB;
use App\Domain\{Availability, Catalog};

final class CalendarController extends Base
{
    public function index(): void
    {
        $view = in_array($_GET['view'] ?? '', ['month', 'day', 'product'], true) ? (string) $_GET['view'] : 'month';
        $expFilter = (int) ($_GET['exp'] ?? 0);
        $experiences = DB::all("SELECT e.id, e.kind, t.title FROM experiences e LEFT JOIN experience_translations t ON t.experience_id = e.id AND t.lang = 'es' ORDER BY e.sort_order, e.id");
        $data = ['title' => 'Calendario', 'view' => $view, 'expFilter' => $expFilter, 'experiences' => $experiences];
        $live = "b.status IN ('pending','deposit_paid','paid','hold')";

        if ($view === 'day') {
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) ? (string) $_GET['date'] : today_site();
            $params = [$date, $date, $date];
            $sql = "SELECT b.*, c.name AS customer_name, c.phone AS customer_phone, t.title FROM bookings b
                    JOIN customers c ON c.id = b.customer_id
                    LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
                    WHERE (b.date = ? OR (b.end_date IS NOT NULL AND b.date <= ? AND b.end_date > ?)) AND $live";
            if ($expFilter) {
                $sql .= ' AND b.experience_id = ?';
                $params[] = $expFilter;
            }
            $rows = DB::all($sql . ' ORDER BY t.title, b.time, b.id', $params);
            $groups = [];
            foreach ($rows as $r) {
                $groups[$r['title'] ?: ('#' . $r['experience_id'])][] = $r;
            }
            $data += ['date' => $date, 'groups' => $groups, 'prev' => date('Y-m-d', strtotime($date . ' -1 day')), 'next' => date('Y-m-d', strtotime($date . ' +1 day'))];
        } elseif ($view === 'product') {
            $expId = $expFilter ?: (int) ($experiences[0]['id'] ?? 0);
            $exp = $expId ? Catalog::load($expId) : null;
            $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? (string) $_GET['month'] : date('Y-m');
            $rows = [];
            if ($exp) {
                $count = (int) date('t', strtotime($month . '-01'));
                for ($d = 1; $d <= $count; $d++) {
                    $date = sprintf('%s-%02d', $month, $d);
                    if ($exp['kind'] === 'lodging') {
                        $booked = Availability::lodgingBooked($exp, $date);
                        $rows[] = ['date' => $date, 'time' => null, 'booked' => $booked, 'capacity' => $exp['default_capacity']];
                        continue;
                    }
                    foreach (self::scheduleFor($exp, $date) as $time => $cap) {
                        $rows[] = ['date' => $date, 'time' => $time, 'booked' => Availability::booked($exp, $date, $time), 'capacity' => $cap];
                    }
                }
            }
            $data += ['exp' => $exp, 'month' => $month, 'rows' => $rows,
                'prev' => date('Y-m', strtotime($month . '-01 -1 month')), 'next' => date('Y-m', strtotime($month . '-01 +1 month'))];
        } else {
            $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? (string) $_GET['month'] : date('Y-m');
            $first = $month . '-01';
            $last = date('Y-m-t', strtotime($first));
            $params = [$first, $last];
            $sql = "SELECT b.date, b.experience_id, SUM(b.pax) AS pax, COUNT(*) AS n, t.title FROM bookings b
                    LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
                    WHERE b.date >= ? AND b.date <= ? AND $live";
            if ($expFilter) {
                $sql .= ' AND b.experience_id = ?';
                $params[] = $expFilter;
            }
            $by = [];
            foreach (DB::all($sql . ' GROUP BY b.date, b.experience_id, t.title ORDER BY b.date', $params) as $r) {
                $by[$r['date']][] = $r;
            }
            $data += ['month' => $month, 'byDay' => $by,
                'prev' => date('Y-m', strtotime($first . ' -1 month')), 'next' => date('Y-m', strtotime($first . ' +1 month'))];
        }
        $this->page('calendar', $data);
    }

    /** Horarios con capacidad de una fecha, sin restricciones de venta (vista interna). */
    private static function scheduleFor(array $exp, string $date): array
    {
        $dow = (string) date('N', strtotime($date . ' 12:00:00'));
        $out = [];
        if ($exp['kind'] === 'event') {
            foreach ($exp['event_dates'] as $ev) {
                if ($ev['event_date'] === $date) {
                    $out[$ev['time']] = (int) $ev['capacity'];
                }
            }
            return $out;
        }
        foreach ($exp['schedules'] as $s) {
            if (!in_array($dow, explode(',', (string) $s['weekdays']), true)
                || ($s['valid_from'] && $date < $s['valid_from']) || ($s['valid_to'] && $date > $s['valid_to'])) {
                continue;
            }
            foreach ($s['times'] as $t) {
                $out[$t] = max($out[$t] ?? 0, $s['capacity'] !== null ? (int) $s['capacity'] : $exp['default_capacity']);
            }
        }
        ksort($out);
        return $out;
    }
}
