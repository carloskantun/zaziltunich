<?php use App\Core\Auth; use App\Domain\{BookingService, Catalog}; $cur = $b['currency']; $can = Auth::canWrite(); ?>
<div class="two">
  <section class="panel">
    <h2><?= e(Catalog::text($exp, 'title', 'es')) ?></h2>
    <p><span class="st st-<?= e($b['status']) ?>"><?= e(status_label($b['status'])) ?></span> · origen: <?= e($b['source']) ?> · creada <?= e($b['created_at']) ?></p>
    <p><strong><?= e(date_label($b['date'], 'es')) ?></strong><?= $b['time'] ? ' · ' . e($b['time']) : '' ?><?= $b['end_date'] ? ' → ' . e(date_label($b['end_date'], 'es')) . ' (' . (int) $b['nights'] . ' noches)' : '' ?> · <?= (int) $b['pax'] ?> pax<?= $b['units'] > 1 ? ' · ' . (int) $b['units'] . ' unidades' : '' ?></p>
    <table class="tbl">
      <tr><td>Base</td><td class="r"><?= e(money($b['base_total'], $cur)) ?></td></tr>
      <?php foreach ($extras as $x): ?><tr><td><?= e($x['label']) ?></td><td class="r"><?= e(money($x['amount'], $cur)) ?></td></tr><?php endforeach; ?>
      <tr><td><strong>Total</strong></td><td class="r"><strong><?= e(money($b['total'], $cur)) ?></strong></td></tr>
      <?php if ($b['deposit_due'] < $b['total']): ?><tr><td>Anticipo requerido</td><td class="r"><?= e(money($b['deposit_due'], $cur)) ?></td></tr><?php endif; ?>
      <tr><td>Pagado</td><td class="r"><?= e(money($b['paid_amount'], $cur)) ?></td></tr>
      <tr><td><strong>Saldo</strong></td><td class="r"><strong><?= e(money($b['balance'], $cur)) ?></strong></td></tr>
    </table>
    <h3>Cliente</h3>
    <p><?= e($b['customer_name']) ?><br><a href="mailto:<?= e($b['customer_email']) ?>"><?= e($b['customer_email']) ?></a><br><?= e($b['customer_phone']) ?>
      <?php if ($b['customer_phone']): ?> · <a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $b['customer_phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></p>
    <p><a href="<?= e(url('/voucher/' . $b['code'], $b['lang'])) ?>" target="_blank">Ver voucher</a></p>
  </section>
  <section class="panel">
    <?php if ($can): ?>
    <h3>Estado</h3>
    <form method="post" action="<?= e(raw_url('admin/reservas/' . $b['id'] . '/estado')) ?>" class="inline"><?= csrf_field() ?>
      <select name="status"><?php foreach (BookingService::STATUSES as $s): ?><option value="<?= e($s) ?>"<?= $s === $b['status'] ? ' selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?></select>
      <button class="btn btn-ghost">Cambiar</button></form>
    <h3>Registrar pago</h3>
    <form method="post" action="<?= e(raw_url('admin/reservas/' . $b['id'] . '/pago')) ?>" class="stack"><?= csrf_field() ?>
      <input name="amount" placeholder="Monto" value="<?= e(from_cents($b['balance'] > 0 && $b['paid_amount'] === 0 ? $b['deposit_due'] : $b['balance'])) ?>" required>
      <input name="method" placeholder="Método (efectivo, transferencia…)"><input name="reference" placeholder="Referencia">
      <button class="btn">Registrar pago</button></form>
    <h3>Notas internas</h3>
    <form method="post" action="<?= e(raw_url('admin/reservas/' . $b['id'] . '/notas')) ?>" class="stack"><?= csrf_field() ?>
      <textarea name="notes" rows="4"><?= e($b['notes']) ?></textarea><button class="btn btn-ghost">Guardar notas</button></form>
    <?php endif; ?>
    <h3>Pagos</h3>
    <?php if (!$payments): ?><p class="muted">Sin pagos.</p><?php else: ?>
    <table class="tbl"><?php foreach ($payments as $pm): ?><tr><td><?= e($pm['created_at']) ?></td><td><?= e($pm['gateway']) ?> <?= e($pm['method']) ?> <small><?= e($pm['reference']) ?></small></td><td class="r"><?= e(money($pm['amount'], $pm['currency'])) ?></td></tr><?php endforeach; ?></table>
    <?php endif; ?>
  </section>
</div>
