<?php $barTitle = $page['t']['title']; $barImage = $heroImage ?? null; include __DIR__ . '/_titlebar.php'; ?>
<section class="band content-page <?= $page['dark'] ? 'dark' : 'light' ?>"><div class="wrap prose"><?= \App\Domain\ContentLayout::columns((string) $page['t']['content']) ?></div></section>
