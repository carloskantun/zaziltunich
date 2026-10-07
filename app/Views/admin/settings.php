<div class="two">
<section class="panel"><h2>Sitio y contacto</h2>
  <form method="post" action="<?= e(raw_url('admin/ajustes')) ?>" class="stack"><?= csrf_field() ?>
    <label class="fld"><span>Nombre del sitio</span><input name="site_name" value="<?= e($s['site_name'] ?? '') ?>"></label>
    <label class="fld"><span>WhatsApp (con lada, ej. 5219851234567)</span><input name="contact_whatsapp" value="<?= e($s['contact_whatsapp'] ?? '') ?>"></label>
    <label class="fld"><span>Teléfono para llamadas</span><input name="contact_phone" value="<?= e($s['contact_phone'] ?? '') ?>"></label>
    <label class="fld"><span>Número para SMS (vacío = el de llamadas)</span><input name="contact_sms" value="<?= e($s['contact_sms'] ?? '') ?>"></label>
    <label class="fld"><span>Correo de contacto</span><input type="email" name="contact_email" value="<?= e($s['contact_email'] ?? '') ?>"></label>
    <label class="fld"><span>Teléfono tal como se muestra en el pie (ej. +52 (985) 130-5096)</span><input name="site_phone_label" value="<?= e($s['site_phone_label'] ?? '') ?>"></label>
    <label class="fld"><span>Dirección (pie de página)</span><input name="site_address" value="<?= e($s['site_address'] ?? '') ?>"></label>
    <label class="fld"><span>Facebook (URL)</span><input name="social_facebook" value="<?= e($s['social_facebook'] ?? '') ?>"></label>
    <label class="fld"><span>Instagram (URL)</span><input name="social_instagram" value="<?= e($s['social_instagram'] ?? '') ?>"></label>
    <label class="fld"><span>X (URL)</span><input name="social_x" value="<?= e($s['social_x'] ?? '') ?>"></label>
    <label class="fld"><span>TripAdvisor (URL)</span><input name="social_tripadvisor" value="<?= e($s['social_tripadvisor'] ?? '') ?>"></label>
    <label class="fld"><span>Minutos que se retiene el cupo sin pago (0 = sin límite)</span><input type="number" name="hold_minutes" value="<?= e($s['hold_minutes'] ?? '1440') ?>"></label>
    <label class="fld"><span>Instrucciones de pago (ES)</span><textarea name="payment_instructions_es" rows="4"><?= e($s['payment_instructions_es'] ?? '') ?></textarea></label>
    <label class="fld"><span>Payment instructions (EN)</span><textarea name="payment_instructions_en" rows="4"><?= e($s['payment_instructions_en'] ?? '') ?></textarea></label>
    <button class="btn">Guardar</button></form></section>
<section class="panel"><h2>Usuarios del panel</h2>
  <table class="tbl"><?php foreach ($users as $u): ?><tr><td><?= e($u['name']) ?><br><small><?= e($u['email']) ?></small></td><td><?= e($u['role']) ?></td><td><?= $u['active'] ? 'activo' : 'inactivo' ?></td></tr><?php endforeach; ?></table>
  <h3>Crear o actualizar usuario</h3>
  <p class="muted">Si el correo ya existe, se actualizan rol, estado y (si la escribes) la contraseña. Administrador: todo · Operador: reservas y pagos · Lectura: solo consulta.</p>
  <form method="post" action="<?= e(raw_url('admin/usuarios')) ?>" class="stack"><?= csrf_field() ?>
    <input name="name" placeholder="Nombre"><input type="email" name="email" placeholder="Correo" required><input type="password" name="password" placeholder="Contraseña (mín. 10)" autocomplete="new-password">
    <select name="role"><option value="operator">Operador</option><option value="admin">Administrador</option><option value="viewer">Solo lectura</option></select>
    <label class="chk"><input type="checkbox" name="active" value="1" checked> Activo</label><button class="btn">Guardar usuario</button></form></section>
</div>
