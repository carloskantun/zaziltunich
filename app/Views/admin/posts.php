<div class="toolbar"><span></span><a class="btn" href="<?= e(raw_url('admin/blog/nueva')) ?>">+ Nueva entrada</a></div>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Fecha</th><th>Título</th><th>Dirección</th><th>Estado</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(substr($r['published_at'], 0, 10)) ?></td><td><a href="<?= e(raw_url('admin/blog/' . $r['id'])) ?>"><?= e($r['title'] ?: $r['slug']) ?></a></td><td>/blog/<?= e($r['slug']) ?></td><td><span class="st st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td class="acts"><a href="<?= e(raw_url('admin/blog/' . $r['id'])) ?>">Editar</a><form method="post" action="<?= e(raw_url('admin/blog/' . $r['id'] . '/eliminar')) ?>" onsubmit="return confirm('¿Eliminar entrada?')"><?= csrf_field() ?><button class="link danger">Eliminar</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
