<?php if (!$rows): ?><p class="muted">Sin reservas.</p><?php else: ?>
<div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>Código</th><th>Fecha</th><th>Experiencia</th><th>Cliente</th><th>Pax</th><th>Total</th><th>Estado</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(raw_url('admin/reservas/' . $r['id'])) ?>"><?= e($r['code']) ?></a></td>
      <td><?= e($r['date']) ?><?= $r['time'] ? ' ' . e($r['time']) : '' ?><?= $r['end_date'] ? ' → ' . e($r['end_date']) : '' ?></td>
      <td><?= e($r['title'] ?? '') ?></td>
      <td><?= e($r['customer_name']) ?></td>
      <td><?= (int) $r['pax'] ?></td>
      <td><?= e(money(to_cents($r['total']), $r['currency'])) ?></td>
      <td><span class="st st-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
