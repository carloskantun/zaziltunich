<div class="two">
<section class="panel"><h2>Nuevo bloqueo</h2>
  <form method="post" action="<?= e(raw_url('admin/bloqueos')) ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="add_block">
    <select name="target"><?php foreach ($experiences as $e): ?><option value="e<?= (int) $e['id'] ?>"><?= e($e['title']) ?></option><?php endforeach; ?>
      <?php foreach ($groups as $g): ?><option value="g<?= (int) $g['id'] ?>">Grupo: <?= e($g['name']) ?></option><?php endforeach; ?></select>
    <label class="fld"><span>Desde</span><input type="date" name="date_from" required></label>
    <label class="fld"><span>Hasta (vacío = un día)</span><input type="date" name="date_to"></label>
    <label class="fld"><span>Hora (vacío = todo el día)</span><input type="time" name="time"></label>
    <input name="reason" placeholder="Motivo (mantenimiento, evento privado…)"><button class="btn">Bloquear</button></form>
  <?php if ($blocks): ?><table class="tbl"><?php foreach ($blocks as $b): ?><tr><td><?= e($b['title'] ?: ('Grupo: ' . $b['group_name'])) ?></td><td><?= e($b['date_from']) ?><?= $b['date_to'] !== $b['date_from'] ? ' → ' . e($b['date_to']) : '' ?> <?= e($b['time'] ?? '') ?></td><td><?= e($b['reason']) ?></td>
    <td><form method="post" action="<?= e(raw_url('admin/bloqueos')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="del_block"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="link danger">Quitar</button></form></td></tr><?php endforeach; ?></table><?php endif; ?>
</section>
<section class="panel"><h2>Capacidad compartida</h2>
  <p class="muted">Experiencias que comparten el mismo espacio (ej. dos servicios en el mismo cenote) pueden asignarse a un grupo: el cupo se descuenta entre todas. Se asigna en la ficha de cada experiencia.</p>
  <?php foreach ($groups as $g): ?>
  <form method="post" action="<?= e(raw_url('admin/bloqueos')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="save_group"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
    <input name="name" value="<?= e($g['name']) ?>"><input type="number" name="capacity" value="<?= (int) $g['capacity'] ?>" class="narrow"><button class="btn btn-ghost">Guardar</button><small class="muted"><?= (int) $g['n'] ?> exp.</small></form>
  <form method="post" action="<?= e(raw_url('admin/bloqueos')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="del_group"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><button class="link danger">Eliminar «<?= e($g['name']) ?>»</button></form>
  <?php endforeach; ?>
  <h3>Nuevo grupo</h3>
  <form method="post" action="<?= e(raw_url('admin/bloqueos')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="save_group"><input name="name" placeholder="Nombre" required><input type="number" name="capacity" value="20" class="narrow"><button class="btn">Crear</button></form>
</section></div>
