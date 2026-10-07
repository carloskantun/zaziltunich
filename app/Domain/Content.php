<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\{Database as DB, I18n};

/** Páginas y entradas de blog con traducción (respaldo en español). */
final class Content
{
    public static function tr(string $table, string $fk, int $id, ?string $lang = null): array
    {
        $lang = $lang ?? I18n::lang();
        $rows = [];
        foreach (DB::all("SELECT * FROM $table WHERE $fk = ?", [$id]) as $r) {
            $rows[$r['lang']] = $r;
        }
        $es = $rows['es'] ?? [];
        $cur = $rows[$lang] ?? [];
        // Campo por campo: si la traducción está vacía se usa el español.
        $out = $es;
        foreach ($cur as $k => $v) {
            if ($v !== '' && $v !== null) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    public static function page(string $slug, bool $onlyPublished = true): ?array
    {
        $p = DB::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
        if (!$p || ($onlyPublished && $p['status'] !== 'published')) {
            return null;
        }
        return $p + ['t' => self::tr('page_translations', 'page_id', (int) $p['id'])];
    }

    public static function navPages(): array
    {
        $out = [];
        foreach (DB::all("SELECT * FROM pages WHERE status = 'published' AND show_in_nav = 1 ORDER BY sort_order, id") as $p) {
            $t = self::tr('page_translations', 'page_id', (int) $p['id']);
            $out[] = ['slug' => $p['slug'], 'sort' => (int) $p['sort_order'], 'label' => ($t['nav_label'] ?? '') ?: ($t['title'] ?? $p['slug'])];
        }
        return $out;
    }

    public static function postCount(): int
    {
        return (int) DB::value("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= ?", [now_site()->format('Y-m-d H:i:s')]);
    }

    public static function posts(int $limit = 24, int $offset = 0): array
    {
        $rows = DB::all("SELECT * FROM posts WHERE status = 'published' AND published_at <= ? ORDER BY published_at DESC, id DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset, [now_site()->format('Y-m-d H:i:s')]);
        foreach ($rows as &$r) {
            $r['t'] = self::tr('post_translations', 'post_id', (int) $r['id']);
        }
        return $rows;
    }

    public static function post(string $slug, bool $onlyPublished = true): ?array
    {
        $p = DB::one('SELECT * FROM posts WHERE slug = ?', [$slug]);
        if (!$p || ($onlyPublished && $p['status'] !== 'published')) {
            return null;
        }
        return $p + ['t' => self::tr('post_translations', 'post_id', (int) $p['id'])];
    }

    public static function media(?string $file): ?string
    {
        return $file ? raw_url('uploads/' . ltrim($file, '/')) : null;
    }
}
