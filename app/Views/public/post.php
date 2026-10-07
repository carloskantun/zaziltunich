<?php $barTitle = $post['t']['title']; $barImage = $heroImage ?? null; include __DIR__ . '/_titlebar.php'; ?>
<section class="band light blog-page"><div class="wrap blog-layout">
  <article class="blog-main prose post">
    <p class="muted"><?= e(date_label(substr($post['published_at'], 0, 10))) ?></p>
    <?= $post['t']['content'] ?>
    <p><a class="btn btn-ghost-dark" href="<?= e(url('/blog')) ?>">← <?= e(t('nav.blog')) ?></a></p>
  </article>
  <?php include __DIR__ . '/_blog_sidebar.php'; ?>
</div></section>
