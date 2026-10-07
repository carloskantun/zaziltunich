<?php use App\Domain\Catalog; ?>
<div class="summary">
  <p><?= e(t('pay.code')) ?>: <strong class="code"><?= e($b['code']) ?></strong> · <?= e(status_label($b['status'])) ?></p>
  <h3><?= e(Catalog::text($exp, 'title', $b['lang'])) ?></h3>
  <p><?= e(date_label($b['date'])) ?><?= $b['time'] ? ' · ' . e($b['time']) : '' ?><?= $b['end_date'] ? ' → ' . e(date_label($b['end_date'])) : '' ?> · <?= (int) $b['pax'] ?> <?= e(t('book.people')) ?></p>
  <p class="line"><span><?= e(t('book.cost')) ?></span><strong><?= e(money($b['total'], $b['currency'])) ?></strong></p>
  <?php foreach ($extras as $x): ?><p class="line small"><span><?= e($x['label']) ?></span><span><?= e(money($x['amount'], $b['currency'])) ?></span></p><?php endforeach; ?>
  <?php if ($b['deposit_due'] < $b['total']): ?><p class="line"><span><?= e(t('book.deposit_now')) ?></span><strong><?= e(money($b['deposit_due'], $b['currency'])) ?></strong></p><?php endif; ?>
</div>
