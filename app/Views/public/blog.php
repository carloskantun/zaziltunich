<?php $barTitle = t('nav.blog'); include __DIR__ . '/_titlebar.php'; ?>
<section class="band light"><div class="wrap"><div class="grid">
<?php foreach ($posts as $p): $img = \App\Domain\Content::media($p['image']); ?>
  <article class="card">
    <a class="card-img" href="<?= e(url('/blog/' . $p['slug'])) ?>"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy"><?php else: ?><span class="ph"></span><?php endif; ?></a>
    <div class="card-body">
      <small class="muted"><?= e(date_label(substr($p['published_at'], 0, 10))) ?></small>
      <h3><a href="<?= e(url('/blog/' . $p['slug'])) ?>"><?= e($p['t']['title']) ?></a></h3>
      <p class="excerpt"><?= e(mb_strimwidth(strip_tags((string) $p['t']['excerpt']), 0, 160, '…')) ?></p>
      <a class="btn btn-sm" href="<?= e(url('/blog/' . $p['slug'])) ?>"><?= e(t('blog.read')) ?></a>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php if (($pages ?? 1) > 1): ?>
  <nav class="pager" aria-label="Blog">
    <?php if ($page > 1): ?><a href="<?= e(url('/blog') . '?p=' . ($page - 1)) ?>">‹</a><?php endif; ?>
    <?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e(url('/blog') . '?p=' . $i) ?>"<?= $i === $page ? ' class="on"' : '' ?>><?= $i ?></a><?php endfor; ?>
    <?php if ($page < $pages): ?><a href="<?= e(url('/blog') . '?p=' . ($page + 1)) ?>">›</a><?php endif; ?>
  </nav>
<?php endif; ?>
</div></section>
