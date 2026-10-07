<form class="filters" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Buscar cliente"><button class="btn btn-ghost">Buscar</button></form>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Reservas pagadas</th><th>Pagado</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td><td><?= (int) $r['bookings'] ?></td><td><?= e(money(to_cents($r['paid']))) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
