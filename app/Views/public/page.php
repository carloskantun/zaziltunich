<?php $barTitle = $page['t']['title']; $barImage = $heroImage ?? null; include __DIR__ . '/_titlebar.php'; ?>
<section class="band <?= $page['dark'] ? 'dark' : 'light' ?>"><div class="wrap prose"><?= $page['t']['content'] ?></div></section>
