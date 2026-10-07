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

<?php
$starSvg = static fn(int $px): string => '<svg class="st" width="' . $px . '" height="' . $px . '" viewBox="0 0 576 512" aria-hidden="true"><path fill="currentColor" d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>';
$stars = static fn(int $px): string => '<span class="stars5" role="img" aria-label="5/5">' . str_repeat($starSvg($px), 5) . '</span>';
?>
<?php if (!empty($rev['items'])): ?>
<section class="home-reviews">
  <div class="rv-w">
    <div class="rv-head">
      <div class="rv-l">
        <div class="rv-id"><span class="ta-badge"><?= icon('tripadvisor') ?></span><p><?= e(t('home.reviews')) ?></p></div>
        <div class="rv-rate"><p class="n"><?= e($rev['rating'] ?? '5.0') ?></p><?= $stars(21) ?><?php if (!empty($rev['count'])): ?><p class="c">(<?= e($rev['count']) ?>)</p><?php endif; ?></div>
      </div>
      <a class="rv-btn" href="<?= e($taUrl) ?>" target="_blank" rel="noopener"><?= e(t('home.write_review')) ?></a>
    </div>
    <div class="rv-slider" data-ms="5000">
      <div class="rv-view"><div class="rv-track">
        <?php foreach ($rev['items'] as $r): ?>
        <div class="rv-slide"><figure class="rev">
          <div class="rev-body">
            <svg class="rq" width="25" height="25" viewBox="0 0 512 512" aria-hidden="true"><path fill="currentColor" d="M0 216C0 149.7 53.7 96 120 96h8c17.7 0 32 14.3 32 32s-14.3 32-32 32h-8c-30.9 0-56 25.1-56 56v8h64c35.3 0 64 28.7 64 64v64c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V216zm256 0c0-66.3 53.7-120 120-120h8c17.7 0 32 14.3 32 32s-14.3 32-32 32h-8c-30.9 0-56 25.1-56 56v8h64c35.3 0 64 28.7 64 64v64c0 35.3-28.7 64-64 64H320c-35.3 0-64-28.7-64-64V216z"/></svg>
            <blockquote><p><?= e($r['text']) ?></p></blockquote>
            <?= $stars(16) ?>
          </div>
          <figcaption>
            <span class="av"><?php if (!empty($r['avatar'])): ?><img src="<?= e(raw_url('uploads/' . $r['avatar'])) ?>" alt="" loading="lazy"><?php else: ?><i><?= e(mb_strtoupper(mb_substr($r['name'] ?: 'T', 0, 1))) ?></i><?php endif; ?><span class="ta-badge sm"><?= icon('tripadvisor') ?></span></span>
            <span class="who"><b><?= e($r['name']) ?></b><small><?= e(t('home.client')) ?></small></span>
          </figcaption>
        </figure></div>
        <?php endforeach; ?>
      </div></div>
      <div class="rv-dots" role="tablist"></div>
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
