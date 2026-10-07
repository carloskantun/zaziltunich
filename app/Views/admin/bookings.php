<?php
use App\Domain\BookingService;
$qs = static function (array $over) use ($filters) {
    return '?' . http_build_query(array_filter($over + $filters, static fn ($v) => $v !== '' && $v !== 0 && $v !== null));
};
?>
<div class="toolbar">
  <div class="chips">
    <a class="chip<?= $filters['status'] === '' ? ' on' : '' ?>" href="<?= e(raw_url('admin/reservas')) ?>">Todas</a>
    <?php foreach (BookingService::STATUSES as $s): ?>
      <a class="chip<?= $filters['status'] === $s ? ' on' : '' ?>" href="<?= e(raw_url('admin/reservas') . $qs(['status' => $s, 'page' => 1])) ?>"><?= e(status_label($s)) ?> <b><?= (int) ($counts[$s] ?? 0) ?></b></a>
    <?php endforeach; ?>
  </div>
  <a class="btn" href="<?= e(raw_url('admin/reservas/nueva')) ?>">+ Nueva reserva</a>
</div>
<form class="filters" method="get" action="<?= e(raw_url('admin/reservas')) ?>">
  <input name="q" value="<?= e($filters['q']) ?>" placeholder="Código, nombre, correo o teléfono">
  <select name="exp"><option value="0">Todas las experiencias</option>
    <?php foreach ($experiences as $e): ?><option value="<?= (int) $e['id'] ?>"<?= (int) $filters['exp'] === (int) $e['id'] ? ' selected' : '' ?>><?= e($e['title']) ?></option><?php endforeach; ?></select>
  <input type="date" name="from" value="<?= e($filters['from']) ?>"><input type="date" name="to" value="<?= e($filters['to']) ?>">
  <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
  <button class="btn btn-ghost">Filtrar</button>
</form>
<p class="muted"><?= (int) $total ?> reservas</p>
<?php include __DIR__ . '/_booking_table.php'; ?>
<?php if ($pages > 1): ?><div class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?><a class="chip<?= $i === $page ? ' on' : '' ?>" href="<?= e(raw_url('admin/reservas') . $qs(['page' => $i])) ?>"><?= $i ?></a><?php endfor; ?></div><?php endif; ?>
