<?php $barTitle = $heading ?? t('nav.blog'); $barImage = $barImage ?? null; include __DIR__ . '/_titlebar.php'; ?>
<section class="band light blog-page"><div class="wrap blog-layout">
  <div class="blog-main">
    <?php if (!empty($query)): ?><p class="muted"><?= e(t('blog.results')) ?> “<?= e($query) ?>”</p><?php endif; ?>
    <?php if (!$posts): ?><p><?= e(t('blog.empty')) ?></p><?php endif; ?>
    <div class="grid two">
    <?php foreach ($posts as $p): $img = \App\Domain\Content::media($p['image']); $href = url('/blog/' . $p['slug']); ?>
      <article class="card post-card">
        <a class="card-img" href="<?= e($href) ?>"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy"><?php else: ?><span class="ph"></span><?php endif; ?></a>
        <div class="card-body">
          <h3><a href="<?= e($href) ?>"><?= e($p['t']['title']) ?></a></h3>
          <p class="excerpt"><?= e(mb_strimwidth(strip_tags((string) $p['t']['excerpt']), 0, 115, '…')) ?></p>
          <div class="card-foot"><small><?= e(date_label(substr($p['published_at'], 0, 10))) ?></small><a class="btn btn-sm" href="<?= e($href) ?>"><?= e(t('blog.read')) ?></a></div>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
    <?php include __DIR__ . '/_pager.php'; ?>
    <?php $guide = site_file('guia.webp'); ?>
    <h2 class="line-title"><span><?= e(t('blog.guide')) ?></span></h2>
    <a class="guide" href="<?= e(url('/descargar-pdf')) ?>"<?= $guide ? ' style="background-image:linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,.45)),url(' . e($guide) . ')"' : '' ?>><span class="btn"><?= e(t('blog.download')) ?></span></a>
  </div>
  <?php include __DIR__ . '/_blog_sidebar.php'; ?>
</div></section>
