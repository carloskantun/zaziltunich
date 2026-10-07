<?php
use App\Domain\Catalog;
use App\Domain\Pricing;
$lang = \App\Core\I18n::lang();
$T = static fn (string $f) => Catalog::text($exp, $f);
$img = $exp['hero_image'] ? raw_url('uploads/' . $exp['hero_image']) : null;
$tabs = [];
if ($T('description')) { $tabs['description'] = ['exp.tab.description', 'text', $T('description')]; }
if ($T('includes') || $T('excludes')) { $tabs['includes'] = ['exp.tab.includes', 'inc', null]; }
if ($T('bring')) { $tabs['bring'] = ['exp.tab.bring', 'list', lines($T('bring'))]; }
if ($T('itinerary')) { $tabs['itinerary'] = ['exp.tab.itinerary', 'list', lines($T('itinerary'))]; }
if ($T('faq')) { $tabs['faq'] = ['exp.tab.faq', 'faq', $T('faq')]; }
?>
<?php
$barTitle = $T('title');
$barImage = null; // usa la imagen general del sitio
include __DIR__ . '/_titlebar.php';
$gallery = array_values(array_filter(array_merge($exp['hero_image'] ? [$exp['hero_image']] : [], (array) json_decode((string) $exp['gallery'], true))));
$gallery = array_values(array_unique($gallery));
?>
<section class="band light exp-band"><div class="wrap exp-layout">
  <div class="exp-info">
    <?php if ($gallery): ?>
    <div class="gal" id="gal">
      <div class="gal-main"><img id="gal-img" src="<?= e(raw_url('uploads/' . $gallery[0])) ?>" alt="<?= e($T('title')) ?>"></div>
      <?php if (count($gallery) > 1): ?><div class="gal-thumbs"><?php foreach ($gallery as $i => $g): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-src="<?= e(raw_url('uploads/' . $g)) ?>"><img src="<?= e(raw_url('uploads/' . $g)) ?>" alt="" loading="lazy"></button><?php endforeach; ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <p class="from"><?= e(t('card.from')) ?> <?= e(money(Catalog::fromPrice($exp), $exp['currency'])) ?></p>
    <?php if ($T('highlights')): ?><ul class="highlights"><?php foreach (array_slice(lines($T('highlights')), 0, 4) as $h): ?><li><i></i><span><?= e($h) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($tabs): ?>
    <div class="tabs" role="tablist">
      <?php $first = true; foreach ($tabs as $key => $tab): ?>
        <button type="button" class="tab<?= $first ? ' on' : '' ?>" data-tab="<?= e($key) ?>"><?= e(t($tab[0])) ?></button>
      <?php $first = false; endforeach; ?>
    </div>
    <?php $first = true; foreach ($tabs as $key => [$lbl, $type, $val]): ?>
      <div class="tab-panel<?= $first ? ' on' : '' ?>" data-panel="<?= e($key) ?>">
        <?php if ($type === 'text'): foreach (preg_split('/\R{2,}/', $val) as $para): ?><p><?= nl2br(e(trim($para))) ?></p><?php endforeach;
        elseif ($type === 'list'): ?><ul class="checks"><?php foreach ($val as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul>
        <?php elseif ($type === 'inc'): ?>
          <?php if ($T('includes')): ?><h4><?= e(t('exp.includes')) ?></h4><ul class="checks"><?php foreach (lines($T('includes')) as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul><?php endif; ?>
          <?php if ($T('excludes')): ?><h4><?= e(t('exp.excludes')) ?></h4><ul class="crosses"><?php foreach (lines($T('excludes')) as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php else: // faq: "Pregunta?|Respuesta" por línea
          foreach (lines($val) as $row): [$qq, $aa] = array_pad(explode('|', $row, 2), 2, ''); ?>
            <details><summary><?= e($qq) ?></summary><p><?= e($aa) ?></p></details>
        <?php endforeach; endif; ?>
      </div>
    <?php $first = false; endforeach; endif; ?>
    <?php if ($T('meeting_point')): ?><p class="meeting"><strong><?= e(t('exp.meeting_point')) ?>:</strong> <?= e($T('meeting_point')) ?></p><?php endif; ?>
  </div>

  <aside class="booking" id="booking">
    <?php if ($exp['kind'] === 'quote'): ?>
      <h3><?= e(t('contact.title')) ?></h3>
      <div class="contact-row"><?php foreach ($contact as $c): ?><a class="btn" href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a><?php endforeach; ?></div>
    <?php else: ?>
    <?php $formAction = url('/checkout'); $adminMode = false; include __DIR__ . '/_widget.php'; ?>
    <?php endif; ?>
  </aside>
</div></section>
<?php if ($contact && $exp['show_contact'] && $exp['kind'] !== 'quote'): ?>
<section class="band light contact-band"><h3><?= e(t('contact.title')) ?></h3><div class="contact-row"><?php foreach ($contact as $c): ?><a class="btn btn-ghost" href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a><?php endforeach; ?></div></section>
<?php endif; ?>
