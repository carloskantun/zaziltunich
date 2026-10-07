<p class="muted">Un grupo de extras se asigna a una o varias experiencias. <b>Selección</b>: lista (una opción), casillas (varias) o cantidad. <b>Modo</b>: manual = el cliente elige; automático = la tarifa se toma según el número de personas (ej. transporte). Cobro: fijo, por persona o por unidad/cantidad.</p>
<form method="post" action="<?= e(raw_url('admin/extras')) ?>"><?= csrf_field() ?>
<div data-repeater="groups">
<?php
$optRow = static function ($i, $j, $o) { ob_start(); ?>
  <div class="rep-row opt"><input type="hidden" name="groups[<?= $i ?>][options][<?= $j ?>][id]" value="<?= (int) ($o['id'] ?? 0) ?>">
    <input name="groups[<?= $i ?>][options][<?= $j ?>][label_es]" value="<?= e($o['label_es'] ?? '') ?>" placeholder="Opción (ES)"><input name="groups[<?= $i ?>][options][<?= $j ?>][label_en]" value="<?= e($o['label_en'] ?? '') ?>" placeholder="Option (EN)">
    <select name="groups[<?= $i ?>][options][<?= $j ?>][charge]"><?php foreach (['fixed' => 'Fijo', 'per_person' => 'Por persona', 'per_unit' => 'Por unidad'] as $k => $l): ?><option value="<?= $k ?>"<?= ($o['charge'] ?? 'fixed') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <input name="groups[<?= $i ?>][options][<?= $j ?>][amount]" value="<?= e(isset($o['amount']) ? number_format((float) $o['amount'], 2, '.', '') : '') ?>" placeholder="Monto" class="narrow">
    <input type="number" name="groups[<?= $i ?>][options][<?= $j ?>][pax_min]" value="<?= e($o['pax_min'] ?? '') ?>" placeholder="pax mín" class="narrow"><input type="number" name="groups[<?= $i ?>][options][<?= $j ?>][pax_max]" value="<?= e($o['pax_max'] ?? '') ?>" placeholder="pax máx" class="narrow">
    <input type="number" name="groups[<?= $i ?>][options][<?= $j ?>][max_qty]" value="<?= e($o['max_qty'] ?? '') ?>" placeholder="cant. máx" class="narrow"><button type="button" class="rep-del">✕</button></div>
<?php return ob_get_clean(); };
$groupRow = static function ($i, $g) use ($optRow) { ob_start(); ?>
  <section class="panel rep-row group"><input type="hidden" name="groups[<?= $i ?>][id]" value="<?= (int) ($g['id'] ?? 0) ?>">
    <div class="cols">
      <label class="fld"><span>Nombre (ES)</span><input name="groups[<?= $i ?>][name_es]" value="<?= e($g['name_es'] ?? '') ?>"></label>
      <label class="fld"><span>Name (EN)</span><input name="groups[<?= $i ?>][name_en]" value="<?= e($g['name_en'] ?? '') ?>"></label>
      <label class="fld"><span>Selección</span><select name="groups[<?= $i ?>][selection]"><?php foreach (['list' => 'Lista', 'checkbox' => 'Casillas', 'quantity' => 'Cantidad'] as $k => $l): ?><option value="<?= $k ?>"<?= ($g['selection'] ?? 'list') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
      <label class="fld"><span>Modo</span><select name="groups[<?= $i ?>][mode]"><option value="manual"<?= ($g['mode'] ?? '') === 'manual' ? ' selected' : '' ?>>Manual</option><option value="auto"<?= ($g['mode'] ?? '') === 'auto' ? ' selected' : '' ?>>Automático por personas</option></select></label>
      <label class="chk"><input type="checkbox" name="groups[<?= $i ?>][required]" value="1"<?= !empty($g['required']) ? ' checked' : '' ?>> Obligatorio</label>
    </div>
    <div class="cols"><label class="fld"><span>Ayuda (ES)</span><input name="groups[<?= $i ?>][help_es]" value="<?= e($g['help_es'] ?? '') ?>"></label><label class="fld"><span>Help (EN)</span><input name="groups[<?= $i ?>][help_en]" value="<?= e($g['help_en'] ?? '') ?>"></label></div>
    <div data-repeater="groups[<?= $i ?>][options]" data-token="__j__" class="opts">
      <?php foreach (($g['options'] ?? []) as $j => $o) { echo $optRow($i, $j, $o); } ?>
      <template><?= $optRow($i, '__j__', []) ?></template>
      <button type="button" class="btn btn-ghost rep-add">+ Opción</button>
    </div>
    <p><button type="button" class="rep-del link danger">Eliminar grupo<?= !empty($g['used']) ? ' (usado en ' . (int) $g['used'] . ' experiencias)' : '' ?></button></p>
  </section>
<?php return ob_get_clean(); };
foreach ($groups as $i => $g) { echo $groupRow($i, $g); } ?>
<template><?= $groupRow('__i__', []) ?></template>
<button type="button" class="btn btn-ghost rep-add">+ Nuevo grupo de extras</button>
</div>
<div class="sticky-save"><button class="btn btn-lg">Guardar extras</button></div>
</form>
