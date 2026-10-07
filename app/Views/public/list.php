<?php $barTitle = t('list.title'); include __DIR__ . '/_titlebar.php'; ?>
<section class="band light"><div class="wrap"><div class="grid">
  <?php foreach ($experiences as $e): include __DIR__ . '/_card.php'; endforeach; ?>
</div></div></section>
