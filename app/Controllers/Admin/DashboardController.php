<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database as DB;
use App\Domain\BookingService;

final class DashboardController extends Base
{
    public function index(): void
    {
        BookingService::expireHolds();
        $today = today_site();
        $in7 = date('Y-m-d', strtotime($today . ' +7 days'));
        $monthStart = date('Y-m-01', strtotime($today));
        $active = "status IN ('pending','deposit_paid','paid','hold')";
        $kpi = [
            'today' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE date = ? AND $active", [$today]),
            'week' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE date >= ? AND date <= ? AND $active", [$today, $in7]),
            'pending_n' => (int) DB::value("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','hold')"),
            'pending_amt' => to_cents(DB::value("SELECT COALESCE(SUM(total - paid_amount),0) FROM bookings WHERE status IN ('pending','hold','deposit_paid')")),
            'revenue' => to_cents(DB::value("SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.status = 'succeeded' AND p.created_at >= ?", [$monthStart . ' 00:00:00'])),
        ];
        $upcoming = DB::all(
            "SELECT b.*, c.name AS customer_name, t.title FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
             WHERE b.date >= ? AND b.status IN ('pending','deposit_paid','paid') ORDER BY b.date, b.time LIMIT 12",
            [$today]
        );
        $latest = DB::all(
            "SELECT b.*, c.name AS customer_name, t.title FROM bookings b
             JOIN customers c ON c.id = b.customer_id
             LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
             ORDER BY b.id DESC LIMIT 8"
        );
        $this->page('dashboard', ['kpi' => $kpi, 'upcoming' => $upcoming, 'latest' => $latest, 'title' => 'Resumen']);
    }
}
