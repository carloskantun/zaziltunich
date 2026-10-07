<?php $v = $row ?? ['id' => 0, 'slug' => '', 'status' => 'published', 'image' => null, 'published_at' => date('Y-m-d')];
$t = static fn ($l, $f) => $tr[$l][$f] ?? ''; ?>
<form method="post" action="<?= e(raw_url('admin/blog/guardar')) ?>" enctype="multipart/form-data" class="xform"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
<section class="panel"><div class="cols">
  <label class="fld"><span>Dirección (slug)</span><input name="slug" value="<?= e($v['slug']) ?>"></label>
  <label class="fld"><span>Estado</span><select name="status"><option value="published"<?= $v['status'] === 'published' ? ' selected' : '' ?>>Publicada</option><option value="draft"<?= $v['status'] === 'draft' ? ' selected' : '' ?>>Borrador</option></select></label>
  <label class="fld"><span>Fecha</span><input type="date" name="published_at" value="<?= e(substr($v['published_at'], 0, 10)) ?>"></label>
</div><label class="fld"><span>Imagen destacada</span><input type="file" name="image" accept="image/*"></label>
<?php if ($v['image']): ?><img class="thumb" src="<?= e(raw_url('uploads/' . $v['image'])) ?>" alt=""><?php endif; ?></section>
<?php foreach (['es' => 'Español', 'en' => 'English'] as $l => $n): ?>
<section class="panel"><h2><?= $n ?></h2>
  <label class="fld"><span>Título</span><input name="tr[<?= $l ?>][title]" value="<?= e($t($l, 'title')) ?>"></label>
  <label class="fld"><span>Resumen</span><textarea name="tr[<?= $l ?>][excerpt]" rows="3"><?= e($t($l, 'excerpt')) ?></textarea></label>
  <label class="fld"><span>Contenido (HTML)</span><textarea name="tr[<?= $l ?>][content]" rows="16" style="font-family:monospace"><?= e($t($l, 'content')) ?></textarea></label>
  <div class="cols"><label class="fld"><span>SEO título</span><input name="tr[<?= $l ?>][seo_title]" value="<?= e($t($l, 'seo_title')) ?>"></label><label class="fld"><span>SEO descripción</span><input name="tr[<?= $l ?>][seo_description]" value="<?= e($t($l, 'seo_description')) ?>"></label></div>
</section><?php endforeach; ?>
<div class="sticky-save"><button class="btn btn-lg">Guardar entrada</button><?php if ($v['id']): ?><a href="<?= e(url('/blog/' . $v['slug'])) ?>" target="_blank">Ver ↗</a><?php endif; ?></div></form>
