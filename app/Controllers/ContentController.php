<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Domain\Content;

final class ContentController
{
    private const PER = 12;

    private function archive(string $heading, array $filter, string $base): void
    {
        $pages = max(1, (int) ceil(Content::postCount($filter) / self::PER));
        $cur = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
        View::render('public/blog', [
            'title' => $heading, 'heading' => $heading, 'posts' => Content::posts(self::PER, ($cur - 1) * self::PER, $filter),
            'page' => $cur, 'pages' => $pages, 'pagerBase' => $base, 'query' => $filter['q'] ?? '', 'bodyClass' => 'light',
        ]);
    }

    public function blog(): void
    {
        $q = trim((string) ($_GET['s'] ?? ''));
        $this->archive(t('nav.blog'), $q !== '' ? ['q' => $q] : [], url('/blog') . ($q !== '' ? '?s=' . rawurlencode($q) : ''));
    }

    /** /blog/{slug}: una entrada o, si no existe, una categoría. */
    public function post(array $p): void
    {
        $post = Content::post($p['slug']);
        if (!$post) {
            $cat = Content::category($p['slug']);
            if ($cat) {
                $this->archive($cat['name'], ['category' => (int) $cat['id']], url('/blog/' . $cat['slug']));
                return;
            }
            View::notFound();
        }
        View::render('public/post', [
            'title' => $post['t']['seo_title'] ?: $post['t']['title'], 'description' => $post['t']['seo_description'] ?: strip_tags((string) $post['t']['excerpt']),
            'post' => $post, 'bodyClass' => 'light', 'heroImage' => Content::media($post['image']),
        ]);
    }

    public function page(array $p): void
    {
        $page = Content::page($p['slug']);
        if (!$page) {
            View::notFound();
        }
        View::render('public/page', [
            'title' => $page['t']['seo_title'] ?: $page['t']['title'], 'description' => $page['t']['seo_description'],
            'page' => $page, 'bodyClass' => $page['dark'] ? 'dark' : 'light', 'heroImage' => Content::media($page['hero_image']),
        ]);
    }
}
