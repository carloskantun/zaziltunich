<?php $barTitle = t('checkout.title'); include __DIR__ . '/_titlebar.php'; ?>
<?php use App\Domain\{Catalog, Selection}; ?>
<section class="section narrow">
  <div class="summary">
    <h3><?= e(Catalog::text($exp, 'title')) ?></h3>
    <p><?= e(date_label($sel['date'])) ?><?= $sel['time'] ? ' · ' . e($sel['time']) : '' ?><?= $sel['end_date'] ? ' → ' . e(date_label($sel['end_date'])) : '' ?> · <?= (int) $sel['pax'] ?> <?= e(t('book.people')) ?></p>
    <?php foreach ($q['lines'] as $l): ?><p class="line"><span><?= e($l['label']) ?></span><span><?= e(money($l['amount'], $exp['currency'])) ?></span></p><?php endforeach; ?>
    <?php foreach ($q['extras'] as $l): ?><p class="line"><span><?= e($l['label']) ?></span><span><?= e(money($l['amount'], $exp['currency'])) ?></span></p><?php endforeach; ?>
    <p class="line total"><span><?= e(t('book.cost')) ?></span><strong><?= e(money($q['total'], $exp['currency'])) ?></strong></p>
    <?php if ($q['deposit'] < $q['total']): ?><p class="line"><span><?= e(t('book.deposit_now')) ?></span><strong><?= e(money($q['deposit'], $exp['currency'])) ?></strong></p><p class="line"><span><?= e(t('book.balance')) ?></span><span><?= e(money($q['total'] - $q['deposit'], $exp['currency'])) ?></span></p><?php endif; ?>
  </div>
  <?php if ($errors): ?>
    <div class="alert"><?php foreach ($errors as $m): ?><p><?= e($m) ?></p><?php endforeach; ?><a class="btn" href="<?= e(url('/reservaciones/' . $exp['slug'])) ?>">←</a></div>
  <?php else: ?>
  <form method="post" action="<?= e(url('/checkout/confirmar')) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="exp" value="<?= (int) $exp['id'] ?>">
    <?= Selection::hidden(['date' => $sel['date'], 'end_date' => $sel['end_date'], 'time' => $sel['time'], 'pax' => $sel['pax'], 'units' => $sel['units'], 'variant_id' => $sel['variant_id'], 'extras' => $sel['extras']]) ?>
    <label class="fld"><span><?= e(t('checkout.name')) ?> *</span><input name="name" required maxlength="160"></label>
    <label class="fld"><span><?= e(t('checkout.email')) ?> *</span><input type="email" name="email" required maxlength="190"></label>
    <label class="fld"><span><?= e(t('checkout.phone')) ?></span><input name="phone" maxlength="40" inputmode="tel"></label>
    <label class="fld"><span><?= e(t('checkout.notes')) ?></span><textarea name="notes" rows="3"></textarea></label>
    <button class="btn btn-lg"><?= e(t('checkout.confirm')) ?> — <?= e(money($q['deposit'], $exp['currency'])) ?></button>
  </form>
  <?php endif; ?>
</section>
