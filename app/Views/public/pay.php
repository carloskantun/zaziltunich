<?php $barTitle = t('pay.title'); include __DIR__ . '/_titlebar.php'; ?>
<section class="section narrow">
  <?php include __DIR__ . '/_booking_summary.php'; ?>
  <p><?= nl2br(e($instructions ?: t('pay.manual'))) ?></p>
  <div class="contact-row"><?php foreach ($contact as $c): ?><a class="btn btn-ghost" href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a><?php endforeach; ?></div>
  <p><a class="btn" href="<?= e(url('/voucher/' . $b['code'])) ?>" target="_blank"><?= e(t('voucher.print')) ?></a></p>
</section>
