<?php
use App\Domain\Catalog;
use App\Domain\Content;
use App\Domain\Settings;

$heroImg = is_file(ROOT . '/public/uploads/site/hero-cenote.webp') ? raw_url('uploads/site/hero-cenote.webp') : null;
$intro = Content::page('inicio');
$introHtml = $intro ? trim((string) $intro['t']['content']) : '';
$video = Settings::get('home_video');
$rev = json_decode(Settings::get('home_reviews'), true) ?: [];
$map = Settings::get('home_map');
$loc = Settings::get('home_location_' . (\App\Core\I18n::lang())) ?: Settings::get('home_location_es');
$si = site_info();
$taUrl = $si['social']['tripadvisor'] ?? '#';
// galería: fotos importadas del sitio; si no hay, las de los productos
$gal = [];
foreach (json_decode(Settings::get('home_gallery'), true) ?: [] as $rel) {
    $gal[] = raw_url('uploads/' . $rel);
}
if (count($gal) < 6) {
    $gal = [];
    foreach ($experiences as $ex) {
        if (!empty($ex['hero_image'])) {
            $gal[] = raw_url('uploads/' . $ex['hero_image']);
        }
    }
}
$gal = array_slice($gal, 0, 24);
// portada: diapositivas de fondo
$hs = json_decode(Settings::get('home_hero_slides'), true) ?: [];
$slides = [];
foreach ($hs['items'] ?? [] as $rel) {
    $slides[] = raw_url('uploads/' . $rel);
}
if (!$slides && $heroImg) {
    $slides[] = $heroImg;
}
?>
<section class="hero hero-home">
  <div class="hero-slides" data-ms="<?= (int) ($hs['ms'] ?? 5000) ?>">
    <?php foreach ($slides as $i => $sl): ?><div class="hs<?= $i === 0 ? ' on' : '' ?>" style="background-image:url(<?= e($sl) ?>)"></div><?php endforeach; ?>
  </div>
  <div class="hero-inner">
    <h1><?= e(t('home.hero_title')) ?></h1>
    <p class="gold"><?= e(t('home.hero_sub')) ?></p>
    <a class="btn" href="<?= e(url('/reservaciones')) ?>"><?= e(t('home.hero_cta')) ?></a>
  </div>
  <a class="hero-down" href="#intro" aria-label="↓"><?= icon('arrow-down') ?></a>
</section>

<?php if ($introHtml !== ''): ?>
<section class="home-intro" id="intro"><div class="home-w prose"><?= $introHtml ?></div></section>
<?php endif; ?>

<?php if ($video): ?>
<section class="home-video"><div class="home-w"><div class="embed"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($video) ?>" loading="lazy" allowfullscreen title="Zazil Tunich"></iframe></div></div></section>
<?php endif; ?>

<section class="home-products">
  <div class="home-w">
    <p class="home-tag"><?= e(t('home.tagline')) ?></p>
    <div class="slider hcar">
      <div class="slides">
        <?php foreach ($experiences as $e):
            $title = Catalog::text($e, 'title');
            $img = $e['hero_image'] ? raw_url('uploads/' . $e['hero_image']) : null;
            $href = url('/reservaciones/' . $e['slug']); ?>
        <a class="slide pc" href="<?= e($href) ?>">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy"><?php endif; ?>
          <span class="pc-b"><span class="pc-t"><?= e($title) ?></span><b><?= e(t('card.from')) ?> <?= e(money(Catalog::fromPrice($e), $e['currency'])) ?></b><i class="btn btn-sm"><?= e(t('card.book')) ?></i></span>
        </a>
        <?php endforeach; ?>
      </div>
      <button class="prev" type="button" aria-label="‹"><?= icon('chevron-left') ?></button>
      <button class="next" type="button" aria-label="›"><?= icon('chevron-right') ?></button>
    </div>
  </div>
</section>

<?php if ($gal): ?>
<section class="home-gallery">
  <div class="home-w">
    <h2 class="uline"><?= e(t('home.gallery')) ?></h2>
    <p class="sub"><a href="<?= e($si['social']['instagram'] ?? '#') ?>" target="_blank" rel="noopener"><?= e(t('home.gallery_sub')) ?></a></p>
    <div class="gal-grid"><?php foreach ($gal as $g): ?><img src="<?= e($g) ?>" alt="" loading="lazy"><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($rev['items'])): ?>
<section class="home-reviews">
  <div class="home-w">
    <div class="rev-head">
      <div>
        <div class="rev-t"><?= icon('tripadvisor') ?><span><?= e(t('home.reviews')) ?></span></div>
        <div class="rev-s"><b><?= e($rev['rating'] ?? '5.0') ?></b><span class="stars">★★★★★</span><?php if (!empty($rev['count'])): ?><small>(<?= e($rev['count']) ?>)</small><?php endif; ?></div>
      </div>
      <a class="btn btn-sm" href="<?= e($taUrl) ?>" target="_blank" rel="noopener"><?= e(t('home.write_review')) ?></a>
    </div>
    <div class="rev-row">
      <?php foreach ($rev['items'] as $r): ?>
      <figure class="rev">
        <span class="q">“</span>
        <blockquote><?= e($r['text']) ?></blockquote>
        <span class="stars">★★★★★</span>
        <figcaption><span class="av"><?= e(mb_strtoupper(mb_substr($r['name'] ?: 'T', 0, 1))) ?></span><span><b><?= e($r['name']) ?></b><small><?= e(t('home.client')) ?></small></span></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
if (!$map) {
    $map = 'https://www.google.com/maps?q=' . rawurlencode('Zazil Tunich Cenote Museo, ' . $si['address']) . '&output=embed&hl=' . \App\Core\I18n::lang();
}
if (!trim(strip_tags((string) $loc))) {
    $loc = t('home.location_text');
}
?>
<section class="home-loc">
  <div class="home-w loc-grid">
    <div class="prose"><h2 class="uline left"><?= e(t('home.location')) ?></h2><?= $loc ?></div>
    <div class="loc-map"><iframe src="<?= e($map) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa"></iframe></div>
  </div>
</section>
