<?php
use App\Controllers\Admin\ExperiencesController as X;
$v = $exp ?? [
  'id' => 0, 'slug' => '', 'kind' => 'slot', 'status' => 'draft', 'pricing_model' => 'per_person', 'base_price' => 0, 'currency' => 'MXN',
  'min_pax' => 1, 'max_pax' => 10, 'lead_hours' => 12, 'max_advance_days' => 365, 'payment_mode' => 'full', 'deposit_percent' => 0,
  'capacity_group_id' => null, 'default_capacity' => 20, 'min_nights' => 1, 'checkin_time' => '', 'checkout_time' => '', 'hero_image' => null,
  'show_contact' => 1, 'sort_order' => 0, 'tr' => [], 'tiers' => [], 'overrides' => [], 'variants' => [], 'schedules' => [], 'event_dates' => [],
];
$tr = static fn (string $l, string $f) => $v['tr'][$l][$f] ?? '';
$sel = static fn ($a, $b) => (string) $a === (string) $b ? ' selected' : '';
$dayNames = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
$days = static function (string $name, ?string $csv) use ($dayNames): string {
    $on = $csv === null || $csv === '' ? [] : explode(',', $csv);
    $h = '<span class="days">';
    foreach ($dayNames as $n => $l) {
        $h .= '<label><input type="checkbox" name="' . $name . '[]" value="' . $n . '"' . (in_array((string) $n, $on, true) ? ' checked' : '') . '>' . $l . '</label>';
    }
    return $h . '</span>';
};
$tplOptions = static function (array $templates, $cur): string {
    $h = '';
    foreach ($templates as $t) {
        $h .= '<option value="' . (int) $t['id'] . '"' . ((int) $cur === (int) $t['id'] ? ' selected' : '') . '>' . e($t['name'] . ' [' . $t['times'] . ']') . '</option>';
    }
    return $h;
};
$money = static fn ($c) => $c === null || $c === '' ? '' : from_cents((int) $c);
?>
<form method="post" action="<?= e(raw_url('admin/experiencias/guardar')) ?>" enctype="multipart/form-data" class="xform">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $v['id'] ?>">

<section class="panel"><h2>General</h2>
  <div class="cols">
    <label class="fld"><span>Estado</span><select name="status"><?php foreach (['draft' => 'Borrador', 'published' => 'Publicada', 'archived' => 'Archivada'] as $k => $l): ?><option value="<?= $k ?>"<?= $sel($v['status'], $k) ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <label class="fld"><span>Dirección (slug)</span><input name="slug" value="<?= e($v['slug']) ?>" placeholder="se genera del título"></label>
    <label class="fld"><span>Orden</span><input type="number" name="sort_order" value="<?= (int) $v['sort_order'] ?>"></label>
  </div>
  <div class="cols">
    <label class="fld"><span>Tipo de servicio</span><select name="kind"><?php foreach (X::KINDS as $k => $l): ?><option value="<?= $k ?>"<?= $sel($v['kind'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="fld"><span>Modelo de precio</span><select name="pricing_model"><?php foreach (X::MODELS as $k => $l): ?><option value="<?= $k ?>"<?= $sel($v['pricing_model'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="fld"><span>Precio base</span><input name="base_price" value="<?= e(from_cents((int) $v['base_price'])) ?>"></label>
    <label class="fld"><span>Moneda</span><select name="currency"><option<?= $sel($v['currency'], 'MXN') ?>>MXN</option><option<?= $sel($v['currency'], 'USD') ?>>USD</option></select></label>
  </div>
  <p class="muted">Por persona: precio × personas · Escalonado: tarifa según rango de personas · Paquete: precio fijo por reserva · Por noche: precio × noches · Por variante: cada opción tiene su precio.</p>
  <div class="cols">
    <label class="fld"><span>Mín. personas</span><input type="number" name="min_pax" value="<?= (int) $v['min_pax'] ?>" min="1"></label>
    <label class="fld"><span>Máx. personas</span><input type="number" name="max_pax" value="<?= (int) $v['max_pax'] ?>" min="1"></label>
    <label class="fld"><span>Anticipación mínima (horas)</span><input type="number" name="lead_hours" value="<?= (int) $v['lead_hours'] ?>" min="0"></label>
    <label class="fld"><span>Venta hasta (días a futuro)</span><input type="number" name="max_advance_days" value="<?= (int) $v['max_advance_days'] ?>" min="1"></label>
  </div>
  <div class="cols">
    <label class="fld"><span>Cobro al reservar</span><select name="payment_mode"><option value="full"<?= $sel($v['payment_mode'], 'full') ?>>Total</option><option value="deposit"<?= $sel($v['payment_mode'], 'deposit') ?>>Anticipo (porcentaje)</option></select></label>
    <label class="fld"><span>% de anticipo</span><input name="deposit_percent" value="<?= e(rtrim(rtrim((string) $v['deposit_percent'], '0'), '.')) ?>"></label>
    <label class="fld"><span>Capacidad por horario (o unidades)</span><input type="number" name="default_capacity" value="<?= (int) $v['default_capacity'] ?>" min="1"></label>
    <label class="fld"><span>Capacidad compartida con</span><select name="capacity_group_id"><option value="0">— propia —</option><?php foreach ($capGroups as $g): ?><option value="<?= (int) $g['id'] ?>"<?= $sel($v['capacity_group_id'], $g['id']) ?>><?= e($g['name'] . ' (' . $g['capacity'] . ')') ?></option><?php endforeach; ?></select></label>
  </div>
  <div class="cols">
    <label class="fld"><span>Hospedaje: noches mínimas</span><input type="number" name="min_nights" value="<?= (int) $v['min_nights'] ?>" min="1"></label>
    <label class="fld"><span>Check-in</span><input type="time" name="checkin_time" value="<?= e($v['checkin_time']) ?>"></label>
    <label class="fld"><span>Check-out</span><input type="time" name="checkout_time" value="<?= e($v['checkout_time']) ?>"></label>
    <label class="chk"><input type="checkbox" name="show_contact" value="1"<?= $v['show_contact'] ? ' checked' : '' ?>> Mostrar botones de contacto</label>
  </div>
  <label class="fld"><span>Imagen principal</span><input type="file" name="hero" accept="image/jpeg,image/png,image/webp"></label>
  <?php if ($v['hero_image']): ?><img class="thumb" src="<?= e(raw_url('uploads/' . $v['hero_image'])) ?>" alt=""><?php endif; ?>
</section>

<?php foreach (['es' => 'Español', 'en' => 'English'] as $l => $lname): ?>
<section class="panel"><h2>Contenido · <?= $lname ?></h2>
  <label class="fld"><span>Título<?= $l === 'es' ? ' *' : '' ?></span><input name="tr[<?= $l ?>][title]" value="<?= e($tr($l, 'title')) ?>"></label>
  <label class="fld"><span>Subtítulo</span><input name="tr[<?= $l ?>][subtitle]" value="<?= e($tr($l, 'subtitle')) ?>"></label>
  <label class="fld"><span>Descripción (párrafos separados por línea en blanco)</span><textarea name="tr[<?= $l ?>][description]" rows="6"><?= e($tr($l, 'description')) ?></textarea></label>
  <div class="cols">
    <label class="fld"><span>Qué incluye (una línea por elemento)</span><textarea name="tr[<?= $l ?>][includes]" rows="4"><?= e($tr($l, 'includes')) ?></textarea></label>
    <label class="fld"><span>No incluye</span><textarea name="tr[<?= $l ?>][excludes]" rows="4"><?= e($tr($l, 'excludes')) ?></textarea></label>
    <label class="fld"><span>Qué llevar</span><textarea name="tr[<?= $l ?>][bring]" rows="4"><?= e($tr($l, 'bring')) ?></textarea></label>
    <label class="fld"><span>Itinerario</span><textarea name="tr[<?= $l ?>][itinerary]" rows="4"><?= e($tr($l, 'itinerary')) ?></textarea></label>
  </div>
  <label class="fld"><span>Preguntas frecuentes (una por línea: Pregunta|Respuesta)</span><textarea name="tr[<?= $l ?>][faq]" rows="4"><?= e($tr($l, 'faq')) ?></textarea></label>
  <label class="fld"><span>Punto de encuentro</span><input name="tr[<?= $l ?>][meeting_point]" value="<?= e($tr($l, 'meeting_point')) ?>"></label>
  <div class="cols"><label class="fld"><span>SEO título</span><input name="tr[<?= $l ?>][seo_title]" value="<?= e($tr($l, 'seo_title')) ?>"></label>
  <label class="fld"><span>SEO descripción</span><input name="tr[<?= $l ?>][seo_description]" value="<?= e($tr($l, 'seo_description')) ?>"></label></div>
</section>
<?php endforeach; ?>

<section class="panel"><h2>Horarios <small>(tipo «con horarios»)</small></h2>
  <p class="muted">Asigna una o varias plantillas de horarios por días de la semana y vigencia. Se administran en «Horarios».</p>
  <div data-repeater="schedules">
    <?php $schedRow = static function ($i, $s) use ($tplOptions, $templates, $days) { ob_start(); ?>
      <div class="rep-row"><select name="schedules[<?= $i ?>][template_id]"><?= $tplOptions($templates, $s['template_id'] ?? 0) ?></select>
        <?= $days("schedules[$i][weekdays]", $s['weekdays'] ?? '1,2,3,4,5,6,7') ?>
        <input type="date" name="schedules[<?= $i ?>][valid_from]" value="<?= e($s['valid_from'] ?? '') ?>" title="Desde"><input type="date" name="schedules[<?= $i ?>][valid_to]" value="<?= e($s['valid_to'] ?? '') ?>" title="Hasta">
        <input type="number" name="schedules[<?= $i ?>][capacity]" value="<?= e($s['capacity'] ?? '') ?>" placeholder="Cupo" title="Cupo (vacío = general)" class="narrow">
        <button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($v['schedules'] as $i => $s) { echo $schedRow($i, $s); } ?>
    <template><?= $schedRow('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Añadir horario</button>
  </div>
</section>

<section class="panel"><h2>Fechas de evento <small>(tipo «evento»)</small></h2>
  <div data-repeater="events">
    <?php $evRow = static function ($i, $s) { ob_start(); ?>
      <div class="rep-row"><input type="date" name="events[<?= $i ?>][event_date]" value="<?= e($s['event_date'] ?? '') ?>"><input type="time" name="events[<?= $i ?>][time]" value="<?= e($s['time'] ?? '19:00') ?>">
        <input type="number" name="events[<?= $i ?>][capacity]" value="<?= e($s['capacity'] ?? 10) ?>" class="narrow" title="Cupo"><button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($v['event_dates'] as $i => $s) { echo $evRow($i, $s); } ?>
    <template><?= $evRow('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Añadir fecha</button>
  </div>
</section>

<section class="panel"><h2>Tarifas escalonadas <small>(modelo escalonado)</small></h2>
  <div data-repeater="tiers">
    <?php $tierRow = static function ($i, $s) use ($money) { ob_start(); ?>
      <div class="rep-row"><input type="number" name="tiers[<?= $i ?>][min_pax]" value="<?= e($s['min_pax'] ?? '') ?>" placeholder="Desde pax" class="narrow"><input type="number" name="tiers[<?= $i ?>][max_pax]" value="<?= e($s['max_pax'] ?? '') ?>" placeholder="Hasta (vacío = sin tope)" class="narrow">
        <input name="tiers[<?= $i ?>][price]" value="<?= e($money($s['price'] ?? null)) ?>" placeholder="Precio por persona"><button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($v['tiers'] as $i => $s) { echo $tierRow($i, $s); } ?>
    <template><?= $tierRow('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Añadir tarifa</button>
  </div>
</section>

<section class="panel"><h2>Variantes <small>(modelo por variante)</small></h2>
  <div data-repeater="variants">
    <?php $varRow = static function ($i, $s) use ($money) { ob_start(); ?>
      <div class="rep-row"><input type="hidden" name="variants[<?= $i ?>][id]" value="<?= (int) ($s['id'] ?? 0) ?>"><input name="variants[<?= $i ?>][name_es]" value="<?= e($s['name_es'] ?? '') ?>" placeholder="Nombre (ES)"><input name="variants[<?= $i ?>][name_en]" value="<?= e($s['name_en'] ?? '') ?>" placeholder="Name (EN)">
        <input name="variants[<?= $i ?>][price]" value="<?= e($money($s['price'] ?? null)) ?>" placeholder="Precio"><button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($v['variants'] as $i => $s) { echo $varRow($i, $s); } ?>
    <template><?= $varRow('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Añadir variante</button>
  </div>
</section>

<section class="panel"><h2>Precios por fecha, día u hora</h2>
  <p class="muted">Sustituyen el precio base dentro del rango. Si indicas hora o días de la semana, solo aplican a esos.</p>
  <div data-repeater="overrides">
    <?php $ovRow = static function ($i, $s) use ($days, $money) { ob_start(); ?>
      <div class="rep-row"><input name="overrides[<?= $i ?>][label]" value="<?= e($s['label'] ?? '') ?>" placeholder="Nombre (ej. Temporada alta)">
        <input type="date" name="overrides[<?= $i ?>][date_from]" value="<?= e($s['date_from'] ?? '') ?>"><input type="date" name="overrides[<?= $i ?>][date_to]" value="<?= e($s['date_to'] ?? '') ?>">
        <input type="time" name="overrides[<?= $i ?>][time]" value="<?= e($s['time'] ?? '') ?>" title="Hora (opcional)"><?= $days("overrides[$i][weekdays]", $s['weekdays'] ?? null) ?>
        <input name="overrides[<?= $i ?>][price]" value="<?= e($money($s['price'] ?? null)) ?>" placeholder="Precio"><button type="button" class="rep-del">✕</button></div>
    <?php return ob_get_clean(); };
    foreach ($v['overrides'] as $i => $s) { echo $ovRow($i, $s); } ?>
    <template><?= $ovRow('__i__', []) ?></template>
    <button type="button" class="btn btn-ghost rep-add">+ Añadir precio especial</button>
  </div>
</section>

<section class="panel"><h2>Extras</h2>
  <p class="muted">Marca los grupos de extras que se ofrecen aquí (se crean en «Extras»). «Obligatorio» puede anular el valor del grupo.</p>
  <table class="tbl"><?php foreach ($allGroups as $g): $on = array_key_exists((int) $g['id'], $links); $ro = $links[(int) $g['id']] ?? null; ?>
    <tr><td><label class="chk"><input type="checkbox" name="extra_groups[<?= (int) $g['id'] ?>][on]" value="1"<?= $on ? ' checked' : '' ?>> <?= e($g['name_es']) ?></label></td>
      <td><small><?= e($g['selection']) ?> · <?= e($g['mode']) ?></small></td>
      <td><select name="extra_groups[<?= (int) $g['id'] ?>][required]"><option value="">Según el grupo (<?= $g['required'] ? 'obligatorio' : 'opcional' ?>)</option><option value="1"<?= $ro !== null && (string) $ro === '1' ? ' selected' : '' ?>>Obligatorio aquí</option><option value="0"<?= $ro !== null && (string) $ro === '0' ? ' selected' : '' ?>>Opcional aquí</option></select></td></tr>
  <?php endforeach; ?></table>
</section>

<div class="sticky-save"><button class="btn btn-lg">Guardar experiencia</button>
  <?php if ($v['id'] && $v['status'] === 'published'): ?><a href="<?= e(url('/reservaciones/' . $v['slug'])) ?>" target="_blank">Ver en el sitio ↗</a><?php endif; ?></div>
</form>
