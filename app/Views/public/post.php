<?php $barTitle = $post['t']['title']; $barImage = $heroImage ?? null; include __DIR__ . '/_titlebar.php'; ?>
<section class="band light"><article class="wrap narrow prose">
  <p class="muted"><?= e(date_label(substr($post['published_at'], 0, 10))) ?></p>
  <?= $post['t']['content'] ?>
  <p><a class="btn btn-ghost-dark" href="<?= e(url('/blog')) ?>">← <?= e(t('nav.blog')) ?></a></p>
</article></section>
