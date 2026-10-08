<?php
// Banda de título con imagen oscurecida, como en el sitio actual. Variables: $barTitle, $barSub (opcional), $barImage (opcional)
$barImage = $barImage ?? asset('images/header-bg.jpg');
?>
<section class="titlebar"<?= !empty($barImage) ? ' style="background-image:linear-gradient(rgba(0,0,0,.3),rgba(0,0,0,.3)),url(' . e($barImage) . ')"' : '' ?>>
  <h1><?= e($barTitle) ?></h1><span class="rule"></span>
  <?php if (!empty($barSub)): ?><p><?= e($barSub) ?></p><?php endif; ?>
</section>
