<section class="panel"><h2>Nueva categoría</h2>
<form method="post" action="<?= e(raw_url('admin/categorias')) ?>" class="cols"><?= csrf_field() ?>
  <label class="fld"><span>Nombre (ES)</span><input name="name_es" required></label>
  <label class="fld"><span>Name (EN)</span><input name="name_en"></label>
  <label class="fld"><span>Dirección (opcional)</span><input name="slug"></label>
  <label class="fld"><span>Dentro de</span><select name="parent_id"><option value="0">— ninguna —</option><?php foreach ($rows as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name_es'] ?? $r['slug']) ?></option><?php endforeach; ?></select></label>
  <button class="btn">Agregar</button>
</form></section>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>ES</th><th>EN</th><th>Dirección</th><th>Dentro de</th><th>Orden</th><th>Entradas</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><form method="post" action="<?= e(raw_url('admin/categorias')) ?>" id="c<?= (int) $r['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"></form><input form="c<?= (int) $r['id'] ?>" name="name_es" value="<?= e($r['name_es'] ?? '') ?>"></td>
<td><input form="c<?= (int) $r['id'] ?>" name="name_en" value="<?= e($r['name_en'] ?? '') ?>"></td>
<td><input form="c<?= (int) $r['id'] ?>" name="slug" value="<?= e($r['slug']) ?>"></td>
<td><select form="c<?= (int) $r['id'] ?>" name="parent_id"><option value="0">—</option><?php foreach ($rows as $o): if ($o['id'] === $r['id']) { continue; } ?><option value="<?= (int) $o['id'] ?>"<?= (int) $r['parent_id'] === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['name_es'] ?? $o['slug']) ?></option><?php endforeach; ?></select></td>
<td><input form="c<?= (int) $r['id'] ?>" type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>" style="width:70px"></td>
<td><?= (int) $r['n'] ?></td>
<td class="acts"><button form="c<?= (int) $r['id'] ?>" class="link">Guardar</button>
<form method="post" action="<?= e(raw_url('admin/categorias/' . $r['id'] . '/eliminar')) ?>" onsubmit="return confirm('¿Eliminar categoría?')"><?= csrf_field() ?><button class="link danger">Eliminar</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
