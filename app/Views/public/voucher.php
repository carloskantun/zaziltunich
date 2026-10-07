<?php use App\Domain\{Catalog, Settings}; ?>
<!doctype html><html lang="<?= e($b['lang']) ?>"><head><meta charset="utf-8"><title><?= e($b['code']) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap">
<style>body{font-family:Montserrat,sans-serif;max-width:640px;margin:32px auto;padding:0 16px;color:#222}h1{margin:0}.code{font-size:28px;letter-spacing:2px}table{width:100%;border-collapse:collapse;margin:16px 0}td{padding:6px 0;border-bottom:1px solid #ddd}td:last-child{text-align:right}@media print{button{display:none}}</style></head><body>
<h1><?= e(Settings::get('site_name', 'Zazil Tunich')) ?></h1>
<p class="code"><?= e($b['code']) ?></p>
<p><?= e($b['customer_name']) ?> · <?= e(status_label($b['status'])) ?></p>
<h2><?= e(Catalog::text($exp, 'title', $b['lang'])) ?></h2>
<p><?= e(date_label($b['date'], $b['lang'])) ?><?= $b['time'] ? ' · ' . e($b['time']) : '' ?><?= $b['end_date'] ? ' → ' . e(date_label($b['end_date'], $b['lang'])) : '' ?> · <?= (int) $b['pax'] ?> pax</p>
<?php if (Catalog::text($exp, 'meeting_point', $b['lang'])): ?><p><?= e(Catalog::text($exp, 'meeting_point', $b['lang'])) ?></p><?php endif; ?>
<table>
<?php foreach ($extras as $x): ?><tr><td><?= e($x['label']) ?></td><td><?= e(money($x['amount'], $b['currency'])) ?></td></tr><?php endforeach; ?>
<tr><td><strong>Total</strong></td><td><strong><?= e(money($b['total'], $b['currency'])) ?></strong></td></tr>
<tr><td>Pagado / Paid</td><td><?= e(money($b['paid_amount'], $b['currency'])) ?></td></tr>
<tr><td>Saldo / Balance</td><td><?= e(money($b['balance'], $b['currency'])) ?></td></tr>
</table>
<button onclick="window.print()"><?= e(t('voucher.print')) ?></button>
</body></html>
