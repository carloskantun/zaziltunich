<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Domain\{Availability, Catalog, Pricing, Selection};

final class ApiController
{
    private function exp(): array
    {
        $exp = Catalog::load((int) ($_GET['exp'] ?? $_POST['exp'] ?? 0));
        if (!$exp || $exp['status'] !== 'published') {
            json_out(['error' => 'not_found'], 404);
        }
        return $exp;
    }

    public function month(): void
    {
        $exp = $this->exp();
        $ym = (string) ($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            json_out(['error' => 'bad_month'], 400);
        }
        json_out(['month' => $ym, 'days' => Availability::month($exp, $ym)]);
    }

    public function slots(): void
    {
        $exp = $this->exp();
        $date = (string) ($_GET['date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            json_out(['error' => 'bad_date'], 400);
        }
        json_out(['date' => $date, 'slots' => Availability::slots($exp, $date)]);
    }

    public function quote(): void
    {
        $exp = $this->exp();
        $sel = Selection::fromInput($_POST);
        $q = Pricing::quote($exp, $sel);
        $cur = $exp['currency'];
        $fmt = static fn (array $l) => ['label' => $l['label'], 'amount' => money($l['amount'], $cur)];
        $messages = array_map(static fn ($e) => t($e['code'], $e['vars'] ?? []), $q['errors']);
        json_out([
            'ok' => $q['ok'],
            'errors' => $messages,
            'nights' => $q['nights'],
            'lines' => array_map($fmt, $q['lines']),
            'extras' => array_map($fmt, $q['extras']),
            'total' => money($q['total'], $cur),
            'deposit' => money($q['deposit'], $cur),
            'balance' => money($q['total'] - $q['deposit'], $cur),
            'has_deposit' => $q['deposit'] < $q['total'],
        ]);
    }
}
