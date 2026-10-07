<?php
use App\Domain\Catalog;
$title = Catalog::text($e, 'title');
$img = $e['hero_image'] ? raw_url('uploads/' . $e['hero_image']) : null;
?>
<article class="card">
  <a class="card-img" href="<?= e(url('/reservaciones/' . $e['slug'])) ?>">
    <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy"><?php else: ?><span class="ph"></span><?php endif; ?>
  </a>
  <div class="card-body">
    <h3><a href="<?= e(url('/reservaciones/' . $e['slug'])) ?>"><?= e($title) ?></a></h3>
    <p class="price"><?= e(t('card.from')) ?> <strong><?= e(money(Catalog::fromPrice($e), $e['currency'])) ?></strong></p>
    <a class="btn btn-sm" href="<?= e(url('/reservaciones/' . $e['slug'])) ?>"><?= e(t('card.book')) ?></a>
  </div>
</article>
