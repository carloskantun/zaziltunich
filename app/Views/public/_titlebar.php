<?php
// Banda de título con imagen oscurecida, como en el sitio actual. Variables: $barTitle, $barSub (opcional), $barImage (opcional)
$barImage = $barImage ?? (is_file(ROOT . '/public/uploads/site/hero-cenote.webp') ? raw_url('uploads/site/hero-cenote.webp') : null);
?>
<section class="titlebar"<?= !empty($barImage) ? ' style="background-image:linear-gradient(rgba(0,0,0,.55),rgba(0,0,0,.55)),url(' . e($barImage) . ')"' : '' ?>>
  <h1><?= e($barTitle) ?></h1><span class="rule"></span>
  <?php if (!empty($barSub)): ?><p><?= e($barSub) ?></p><?php endif; ?>
</section>
