<?php $cur = 'MXN'; ?>
<div class="kpis">
  <div class="kpi"><small>Reservas hoy</small><strong><?= (int) $kpi['today'] ?></strong></div>
  <div class="kpi"><small>Próximos 7 días</small><strong><?= (int) $kpi['week'] ?></strong></div>
  <div class="kpi"><small>Pendientes de pago</small><strong><?= (int) $kpi['pending_n'] ?></strong></div>
  <div class="kpi"><small>Por cobrar</small><strong><?= e(money($kpi['pending_amt'], $cur)) ?></strong></div>
  <div class="kpi"><small>Cobrado este mes</small><strong><?= e(money($kpi['revenue'], $cur)) ?></strong></div>
</div>
<div class="two">
  <section class="panel"><h2>Próximas reservas</h2>
    <?php $rows = $upcoming; include __DIR__ . '/_booking_table.php'; ?></section>
  <section class="panel"><h2>Últimas creadas</h2>
    <?php $rows = $latest; include __DIR__ . '/_booking_table.php'; ?></section>
</div>
