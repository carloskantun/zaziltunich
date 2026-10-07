<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Domain\Content;

final class ContentController
{
    public function blog(): void
    {
        View::render('public/blog', ['title' => t('nav.blog'), 'posts' => Content::posts(), 'bodyClass' => 'light']);
    }

    public function post(array $p): void
    {
        $post = Content::post($p['slug']);
        if (!$post) {
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
