<?php
// Variables: $page, $pages, $pagerBase (URL base sin ?p)
if (($pages ?? 1) <= 1) { return; }
$sep = str_contains($pagerBase, '?') ? '&' : '?';
$show = [];
foreach ([1, 2, 3, $page - 1, $page, $page + 1, $pages] as $n) { if ($n >= 1 && $n <= $pages) { $show[$n] = true; } }
ksort($show);
?>
<nav class="pager" aria-label="<?= e(t('nav.blog')) ?>">
  <?php $prev = 0; foreach (array_keys($show) as $n): if ($prev && $n > $prev + 1): ?><span>…</span><?php endif; $prev = $n; ?>
    <?php if ($n === $page): ?><span class="on"><?= $n ?></span><?php else: ?><a href="<?= e($pagerBase . $sep . 'p=' . $n) ?>"><?= $n ?></a><?php endif; ?>
  <?php endforeach; ?>
  <?php if ($page < $pages): ?><a class="nx" href="<?= e($pagerBase . $sep . 'p=' . ($page + 1)) ?>"><?= e(t('blog.next')) ?></a><?php endif; ?>
</nav>
