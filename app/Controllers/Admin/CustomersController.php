<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database as DB;

final class CustomersController extends Base
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $params = [];
        $where = '1=1';
        if ($q !== '') {
            $where = '(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
            $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
        }
        $rows = DB::all(
            "SELECT c.*, COUNT(b.id) AS bookings, COALESCE(SUM(b.paid_amount),0) AS paid FROM customers c
             LEFT JOIN bookings b ON b.customer_id = c.id AND b.status IN ('deposit_paid','paid')
             WHERE $where GROUP BY c.id ORDER BY c.id DESC LIMIT 200",
            $params
        );
        $this->page('customers', ['title' => 'Clientes', 'rows' => $rows, 'q' => $q]);
    }
}
