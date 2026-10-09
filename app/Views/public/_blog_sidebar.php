<?php
use App\Domain\{Catalog, Content};
$si = site_info();
$cats = Content::categories();
// El widget de WordPress ordena las categorías por nombre, conservando su jerarquía.
$sortCats = static function (array &$list) use (&$sortCats): void {
    usort($list, static fn ($a, $b) => strnatcasecmp(
        strtr(mb_strtolower($a['name']), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']),
        strtr(mb_strtolower($b['name']), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'])
    ));
    foreach ($list as &$category) {
        $sortCats($category['children']);
    }
    unset($category);
};
$sortCats($cats);
$dark = site_file('logo-dark.png');
$ig = [];
foreach (glob(ROOT . '/public/uploads/site/ig/*.{webp,jpg,png}', GLOB_BRACE) ?: [] as $f) {
    $ig[] = raw_url('uploads/site/ig/' . basename($f));
}
$igUrl = $si['social']['instagram'] ?? '#';
$services = Catalog::published();
$renderCats = static function (array $list, int $depth) use (&$renderCats): void {
    echo '<ul class="cats' . ($depth ? ' sub' : '') . '">';
    foreach ($list as $c) {
        echo '<li><a href="' . e(url('/blog/' . $c['slug'])) . '">' . icon('angle-right') . '<span>' . e($c['name']) . ' (' . (int) $c['count'] . ')</span></a>';
        if (!empty($c['children'])) {
            $renderCats(array_values(array_filter($c['children'], static fn ($x) => $x['count'] > 0)), $depth + 1);
        }
        echo '</li>';
    }
    echo '</ul>';
};
?>
<aside class="side">
  <section class="widget"><h3 class="wt"><?= e(t('blog.follow')) ?></h3>
    <div class="social sq"><?php foreach (array_intersect_key(array_replace(array_fill_keys(['facebook-f', 'instagram', 'tripadvisor'], ''), $si['social']), array_flip(['facebook-f', 'instagram', 'tripadvisor'])) as $ic => $href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($ic) ?>"><?= icon($ic) ?></a><?php endforeach; ?></div>
  </section>
  <section class="widget">
    <form class="search" method="get" action="<?= e(url('/blog')) ?>" role="search"><input type="search" name="s" placeholder="<?= e(t('blog.search')) ?>" value="<?= e($_GET['s'] ?? '') ?>" aria-label="<?= e(t('blog.search')) ?>"><button aria-label="<?= e(t('blog.search')) ?>"><?= icon('magnifying-glass') ?></button></form>
  </section>
  <?php if ($cats): ?><section class="widget"><h3 class="wt"><?= e(t('blog.categories')) ?></h3><?php $renderCats($cats, 0); ?></section><?php endif; ?>
  <?php if ($dark): ?><section class="widget side-logo"><img src="<?= e($dark) ?>" alt="" loading="lazy"></section><?php endif; ?>
  <section class="widget"><h3 class="wt"><?= e(t('blog.need_info')) ?></h3><a class="btn btn-block" href="<?= e($si['wa']) ?>" target="_blank" rel="noopener"><?= e(t('blog.contact')) ?></a></section>
  <?php if ($ig): ?>
  <section class="widget"><h3 class="wt"><?= e(t('blog.instagram')) ?></h3>
    <a class="ig-head" href="<?= e($igUrl) ?>" target="_blank" rel="noopener"><span class="ig-av"><?= icon('instagram') ?></span>zaziltunich</a>
    <div class="ig-grid"><?php foreach (array_slice($ig, 0, 9) as $u): ?><a href="<?= e($igUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($u) ?>" alt="" loading="lazy"></a><?php endforeach; ?></div>
    <a class="btn btn-sm btn-ink" href="<?= e($igUrl) ?>" target="_blank" rel="noopener"><?= e(t('blog.more_photos')) ?></a>
    <a class="btn btn-sm" href="<?= e($igUrl) ?>" target="_blank" rel="noopener"><?= icon('instagram') ?> <?= e(t('blog.follow_ig')) ?></a>
  </section>
  <?php endif; ?>
  <section class="widget"><h3 class="wt"><?= e(t('blog.subscribe_title')) ?></h3><a class="btn btn-block" href="mailto:<?= e($si['email']) ?>?subject=<?= rawurlencode(t('blog.subscribe_title')) ?>"><?= e(t('blog.subscribe')) ?></a></section>
  <?php if ($services): ?>
  <section class="widget"><h3 class="wt"><?= e(t('blog.services')) ?></h3>
    <div class="slider svc" data-slider>
      <div class="slides"><?php foreach ($services as $e): $img = $e['hero_image'] ? raw_url('uploads/' . $e['hero_image']) : null; $ttl = Catalog::text($e, 'title'); ?>
        <a class="slide" href="<?= e(url('/reservaciones/' . $e['slug'])) ?>">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($ttl) ?>" loading="lazy"><?php endif; ?>
          <span class="cap"><strong><?= e($ttl) ?></strong><em><?= e(t('card.from')) ?>: <?= e(money(Catalog::fromPrice($e), $e['currency'])) ?></em></span>
        </a><?php endforeach; ?></div>
      <button class="prev" type="button" aria-label="<?= e(t('blog.prev')) ?>"><?= icon('chevron-left') ?></button><button class="next" type="button" aria-label="<?= e(t('blog.next')) ?>"><?= icon('chevron-right') ?></button>
    </div>
  </section>
  <?php endif; ?>
</aside>
