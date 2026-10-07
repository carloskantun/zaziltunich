<p class="muted">Una plantilla es una lista de horas reutilizable (ej. «Tarde: 15:00, 17:00, 19:00»). Luego se asigna a cada experiencia por días y vigencia.</p>
<form method="post" action="<?= e(raw_url('admin/horarios')) ?>"><?= csrf_field() ?>
  <div data-repeater="tpl">
    <?php $row = static function ($i, $r) { ob_start(); ?>
      <div class="rep-row"><input type="hidden" name="tpl[<?= $i ?>][id]" value="<?= (int) ($r['id'] ?? 0) ?>"><input name="tpl[<?= $i ?>][name]" value="<?= e($r['name'] ?? '') ?>" placeholder="Nombre">
        <input name="tpl[<?= $i ?>][times]" value="<?= e($r['times'] ?? '') ?>" placeholder="09:00, 11:00, 15:00"><?php if (!empty($r['used'])): ?><small class="muted">en uso: <?= (int) $r['used'] ?></small><?php endif; ?><button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($rows as $i => $r) { echo $row($i, $r); } ?>
    <template><?= $row('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Nueva plantilla</button>
  </div>
  <p><button class="btn">Guardar</button></p>
</form>
