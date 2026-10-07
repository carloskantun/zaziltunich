<div class="toolbar"><span></span><a class="btn" href="<?= e(raw_url('admin/paginas/nueva')) ?>">+ Nueva página</a></div>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>#</th><th>Título</th><th>Dirección</th><th>Menú</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int) $r['sort_order'] ?></td><td><a href="<?= e(raw_url('admin/paginas/' . $r['id'])) ?>"><?= e($r['title'] ?: $r['slug']) ?></a></td><td>/<?= e($r['slug']) ?></td><td><?= $r['show_in_nav'] ? 'sí' : '—' ?></td><td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td class="acts"><a href="<?= e(raw_url('admin/paginas/' . $r['id'])) ?>">Editar</a><form method="post" action="<?= e(raw_url('admin/paginas/' . $r['id'] . '/eliminar')) ?>" onsubmit="return confirm('¿Eliminar página?')"><?= csrf_field() ?><button class="link danger">Eliminar</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
