<?php use App\Core\Auth; $isAdmin = Auth::isAdmin(); ?>
<div class="toolbar"><span></span><?php if ($isAdmin): ?><a class="btn" href="<?= e(raw_url('admin/experiencias/nueva')) ?>">+ Nueva experiencia</a><?php endif; ?></div>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>#</th><th>Título</th><th>Tipo</th><th>Modelo</th><th>Precio base</th><th>Pago</th><th>Estado</th><th>Reservas</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?= (int) $r['sort_order'] ?></td>
    <td><a href="<?= e(raw_url('admin/experiencias/' . $r['id'])) ?>"><?= e($r['title'] ?: $r['slug']) ?></a><br><small class="muted">/<?= e($r['slug']) ?></small></td>
    <td><?= e($r['kind']) ?></td><td><?= e($r['pricing_model']) ?></td>
    <td><?= e(money(to_cents($r['base_price']), $r['currency'])) ?></td>
    <td><?= $r['payment_mode'] === 'deposit' ? e(rtrim(rtrim((string) $r['deposit_percent'], '0'), '.')) . '% anticipo' : 'Total' ?></td>
    <td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
    <td><?= (int) $r['n_bookings'] ?></td>
    <td class="acts"><?php if ($isAdmin): ?>
      <a href="<?= e(raw_url('admin/experiencias/' . $r['id'])) ?>">Editar</a>
      <form method="post" action="<?= e(raw_url('admin/experiencias/' . $r['id'] . '/duplicar')) ?>"><?= csrf_field() ?><button class="link">Duplicar</button></form>
      <form method="post" action="<?= e(raw_url('admin/experiencias/' . $r['id'] . '/eliminar')) ?>" onsubmit="return confirm('¿Eliminar? Si tiene reservas se archivará.')"><?= csrf_field() ?><button class="link danger">Eliminar</button></form>
    <?php endif; ?></td>
  </tr>
<?php endforeach; ?>
</tbody></table></div>
