<?php if (!$exp): ?>
<section class="panel"><h2>1. Elige la experiencia</h2>
  <div class="chips"><?php foreach ($experiences as $e): ?><a class="chip" href="<?= e(raw_url('admin/reservas/nueva?exp=' . $e['id'])) ?>"><?= e($e['title']) ?></a><?php endforeach; ?></div>
</section>
<?php else:
  $formAction = raw_url('admin/reservas/nueva'); $adminMode = true;
  $adminExtra = '<label class="fld"><span>Nombre *</span><input name="name" required></label>
  <label class="fld"><span>Correo *</span><input type="email" name="email" required></label>
  <label class="fld"><span>Teléfono</span><input name="phone"></label>
  <label class="fld"><span>Origen</span><select name="source"><option value="phone">Llamada</option><option value="whatsapp">WhatsApp</option><option value="email">Correo</option><option value="walkin">Mostrador</option></select></label>
  <label class="fld"><span>Idioma del cliente</span><select name="lang"><option value="es">Español</option><option value="en">English</option></select></label>
  <label class="fld"><span>Notas</span><textarea name="notes" rows="2"></textarea></label>';
  \App\Core\I18n::set('es'); ?>
<p><a href="<?= e(raw_url('admin/reservas/nueva')) ?>">← Cambiar experiencia</a></p>
<h2><?= e(\App\Domain\Catalog::text($exp, 'title', 'es')) ?></h2>
<div class="booking" style="position:static;max-width:440px"><?php include ROOT . '/app/Views/public/_widget.php'; ?></div>
<?php endif; ?>
