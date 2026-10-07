<?php
use App\Domain\Content;
$heroImg = is_file(ROOT . '/public/uploads/site/hero-cenote.webp') ? raw_url('uploads/site/hero-cenote.webp') : null;
$intro = Content::page('inicio');
?>
<section class="hero"<?= $heroImg ? ' style="background-image:linear-gradient(rgba(0,0,0,.25),rgba(0,0,0,.35)),url(' . e($heroImg) . ')"' : '' ?>>
  <div class="hero-inner">
    <h1><?= e(t('home.hero_title')) ?></h1>
    <p class="gold"><?= e(t('home.hero_sub')) ?></p>
    <a class="btn" href="<?= e(url('/reservaciones')) ?>"><?= e(t('home.hero_cta')) ?></a>
  </div>
</section>
<?php if ($intro && trim((string) $intro['t']['content']) !== ''): ?>
<section class="band dark"><div class="wrap prose"><h2><?= e($intro['t']['title']) ?></h2><?= $intro['t']['content'] ?></div></section>
<?php endif; ?>
<section class="band dark">
  <div class="wrap">
    <h2 class="center"><?= e(t('home.tagline')) ?></h2>
    <div class="grid">
      <?php foreach ($experiences as $e): include __DIR__ . '/_card.php'; endforeach; ?>
    </div>
  </div>
</section>
