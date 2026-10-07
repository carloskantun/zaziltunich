<?php
$base = raw_url('admin/calendario');
$q = static fn (array $a) => '?' . http_build_query(array_filter($a, static fn ($v) => $v !== '' && $v !== 0 && $v !== null));
?>
<div class="toolbar">
  <div class="chips">
    <?php foreach (['month' => 'Mes', 'day' => 'Día', 'product' => 'Por producto'] as $k => $label): ?>
      <a class="chip<?= $view === $k ? ' on' : '' ?>" href="<?= e($base . $q(['view' => $k, 'exp' => $expFilter])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="get" class="inline"><input type="hidden" name="view" value="<?= e($view) ?>">
    <?php if ($view === 'month'): ?><input type="hidden" name="month" value="<?= e($month) ?>"><?php endif; ?>
    <?php if ($view === 'day'): ?><input type="hidden" name="date" value="<?= e($date) ?>"><?php endif; ?>
    <select name="exp" onchange="this.form.submit()"><?php if ($view !== 'product'): ?><option value="0">Todas las experiencias</option><?php endif; ?>
      <?php foreach ($experiences as $e): ?><option value="<?= (int) $e['id'] ?>"<?= (int) $expFilter === (int) $e['id'] ? ' selected' : '' ?>><?= e($e['title']) ?></option><?php endforeach; ?></select></form>
</div>

<?php if ($view === 'month'):
    $first = strtotime($month . '-01'); $days = (int) date('t', $first); $offset = ((int) date('N', $first)) - 1; ?>
  <div class="cal-nav"><a href="<?= e($base . $q(['view' => 'month', 'month' => $prev, 'exp' => $expFilter])) ?>">‹</a>
    <strong><?= e(date_label($month . '-01', 'es')) ?></strong>
    <a href="<?= e($base . $q(['view' => 'month', 'month' => $next, 'exp' => $expFilter])) ?>">›</a></div>
  <div class="mcal">
    <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $d): ?><span class="dow"><?= $d ?></span><?php endforeach; ?>
    <?php for ($i = 0; $i < $offset; $i++): ?><span class="blank"></span><?php endfor; ?>
    <?php for ($d = 1; $d <= $days; $d++): $date = sprintf('%s-%02d', $month, $d); $items = $byDay[$date] ?? []; $pax = array_sum(array_column($items, 'pax')); ?>
      <a class="mday<?= $date === today_site() ? ' today' : '' ?>" href="<?= e($base . $q(['view' => 'day', 'date' => $date, 'exp' => $expFilter])) ?>">
        <b><?= $d ?></b>
        <?php if ($items): ?><em><?= (int) $pax ?> pax</em>
          <?php foreach (array_slice($items, 0, 3) as $it): ?><span class="ev"><?= (int) $it['n'] ?> · <?= e(mb_strimwidth((string) $it['title'], 0, 22, '…')) ?></span><?php endforeach; ?>
          <?php if (count($items) > 3): ?><span class="ev more">+<?= count($items) - 3 ?> más</span><?php endif; ?>
        <?php endif; ?>
      </a>
    <?php endfor; ?>
  </div>

<?php elseif ($view === 'day'): ?>
  <div class="cal-nav"><a href="<?= e($base . $q(['view' => 'day', 'date' => $prev, 'exp' => $expFilter])) ?>">‹</a>
    <strong><?= e(date_label($date, 'es')) ?></strong>
    <a href="<?= e($base . $q(['view' => 'day', 'date' => $next, 'exp' => $expFilter])) ?>">›</a></div>
  <?php if (!$groups): ?><p class="muted">Sin reservas este día.</p><?php endif; ?>
  <?php foreach ($groups as $title => $rows): ?>
    <section class="panel"><h2><?= e($title) ?> <small><?= (int) array_sum(array_column($rows, 'pax')) ?> pax</small></h2>
      <table class="tbl"><?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['time'] ?: ($r['end_date'] ? 'Hasta ' . $r['end_date'] : '')) ?></td>
          <td><a href="<?= e(raw_url('admin/reservas/' . $r['id'])) ?>"><?= e($r['code']) ?></a></td>
          <td><?= e($r['customer_name']) ?> <small><?= e($r['customer_phone']) ?></small></td>
          <td><?= (int) $r['pax'] ?> pax</td>
          <td><span class="st st-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td></tr>
      <?php endforeach; ?></table></section>
  <?php endforeach; ?>

<?php else: ?>
  <div class="cal-nav"><a href="<?= e($base . $q(['view' => 'product', 'month' => $prev, 'exp' => $exp['id'] ?? 0])) ?>">‹</a>
    <strong><?= e(date_label($month . '-01', 'es')) ?></strong>
    <a href="<?= e($base . $q(['view' => 'product', 'month' => $next, 'exp' => $exp['id'] ?? 0])) ?>">›</a></div>
  <?php if (!$rows): ?><p class="muted">Esta experiencia no tiene horarios en el mes.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Fecha</th><th>Hora</th><th>Ocupación</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $pct = $r['capacity'] > 0 ? min(100, (int) round($r['booked'] * 100 / $r['capacity'])) : 0; ?>
    <tr><td><a href="<?= e($base . $q(['view' => 'day', 'date' => $r['date'], 'exp' => $exp['id']])) ?>"><?= e($r['date']) ?></a></td><td><?= e($r['time'] ?? '—') ?></td>
      <td><div class="bar"><i style="width:<?= $pct ?>%"></i></div></td><td><?= (int) $r['booked'] ?> / <?= (int) $r['capacity'] ?></td></tr>
  <?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php endif; ?>
