<section class="section">
  <h1><?= e(t('list.title')) ?></h1>
  <div class="grid">
    <?php foreach ($experiences as $e): include __DIR__ . '/_card.php'; endforeach; ?>
  </div>
</section>
