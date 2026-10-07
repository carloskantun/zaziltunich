<section class="hero">
  <div class="hero-inner">
    <h1><?= e(t('home.hero_title')) ?></h1>
    <p><?= e(t('home.tagline')) ?></p>
    <a class="btn btn-lg" href="<?= e(url('/reservaciones')) ?>"><?= e(t('home.hero_cta')) ?></a>
  </div>
</section>
<section class="section">
  <h2><?= e(t('home.experiences')) ?></h2>
  <div class="grid">
    <?php foreach ($experiences as $e): include __DIR__ . '/_card.php'; endforeach; ?>
  </div>
</section>
