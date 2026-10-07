<?php $v = $row ?? ['id' => 0, 'slug' => '', 'status' => 'published', 'dark' => 1, 'show_in_nav' => 0, 'sort_order' => 0, 'hero_image' => null];
$t = static fn ($l, $f) => $tr[$l][$f] ?? ''; ?>
<form method="post" action="<?= e(raw_url('admin/paginas/guardar')) ?>" enctype="multipart/form-data" class="xform"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
<section class="panel"><div class="cols">
  <label class="fld"><span>Dirección (slug)</span><input name="slug" value="<?= e($v['slug']) ?>" placeholder="mapa-del-recorrido"></label>
  <label class="fld"><span>Estado</span><select name="status"><option value="published"<?= $v['status'] === 'published' ? ' selected' : '' ?>>Publicada</option><option value="draft"<?= $v['status'] === 'draft' ? ' selected' : '' ?>>Borrador</option></select></label>
  <label class="fld"><span>Orden en menú (≥100 = después de Blog)</span><input type="number" name="sort_order" value="<?= (int) $v['sort_order'] ?>"></label>
  <label class="chk"><input type="checkbox" name="show_in_nav" value="1"<?= $v['show_in_nav'] ? ' checked' : '' ?>> Mostrar en el menú</label>
  <label class="chk"><input type="checkbox" name="dark" value="1"<?= $v['dark'] ? ' checked' : '' ?>> Fondo oscuro</label>
</div><label class="fld"><span>Imagen de la banda de título</span><input type="file" name="hero" accept="image/*"></label>
<?php if ($v['hero_image']): ?><img class="thumb" src="<?= e(raw_url('uploads/' . $v['hero_image'])) ?>" alt=""><?php endif; ?></section>
<?php foreach (['es' => 'Español', 'en' => 'English'] as $l => $n): ?>
<section class="panel"><h2><?= $n ?></h2>
  <div class="cols"><label class="fld"><span>Título</span><input name="tr[<?= $l ?>][title]" value="<?= e($t($l, 'title')) ?>"></label><label class="fld"><span>Texto en el menú</span><input name="tr[<?= $l ?>][nav_label]" value="<?= e($t($l, 'nav_label')) ?>"></label></div>
  <label class="fld"><span>Contenido (HTML)</span><textarea name="tr[<?= $l ?>][content]" rows="14" style="font-family:monospace"><?= e($t($l, 'content')) ?></textarea></label>
  <div class="cols"><label class="fld"><span>SEO título</span><input name="tr[<?= $l ?>][seo_title]" value="<?= e($t($l, 'seo_title')) ?>"></label><label class="fld"><span>SEO descripción</span><input name="tr[<?= $l ?>][seo_description]" value="<?= e($t($l, 'seo_description')) ?>"></label></div>
</section><?php endforeach; ?>
<div class="sticky-save"><button class="btn btn-lg">Guardar página</button><?php if ($v['id']): ?><a href="<?= e(url('/' . $v['slug'])) ?>" target="_blank">Ver ↗</a><?php endif; ?></div></form>
