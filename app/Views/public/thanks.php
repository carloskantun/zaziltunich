<section class="section narrow">
  <h1><?= e(t('thanks.title')) ?></h1>
  <?php $extras = \App\Domain\BookingService::extras($b['id']); include __DIR__ . '/_booking_summary.php'; ?>
  <p><?= e(t('thanks.text')) ?></p>
  <p><a class="btn" href="<?= e(url('/voucher/' . $b['code'])) ?>" target="_blank"><?= e(t('voucher.print')) ?></a></p>
</section>
