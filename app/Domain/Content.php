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

    /** Condición SQL y parámetros para publicadas + filtros opcionales (categoría, búsqueda). */
    private static function postWhere(array $f): array
    {
        $sql = "p.status = 'published' AND p.published_at <= ?";
        $par = [now_site()->format('Y-m-d H:i:s')];
        if (!empty($f['category'])) {
            $sql .= ' AND p.id IN (SELECT post_id FROM post_categories WHERE category_id = ?)';
            $par[] = (int) $f['category'];
        }
        if (!empty($f['q'])) {
            $like = '%' . str_replace(['%', '_'], '', (string) $f['q']) . '%';
            $sql .= ' AND p.id IN (SELECT post_id FROM post_translations WHERE title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
            array_push($par, $like, $like, $like);
        }
        return [$sql, $par];
    }

    public static function postCount(array $f = []): int
    {
        [$w, $par] = self::postWhere($f);
        return (int) DB::value("SELECT COUNT(*) FROM posts p WHERE $w", $par);
    }

    public static function posts(int $limit = 24, int $offset = 0, array $f = []): array
    {
        [$w, $par] = self::postWhere($f);
        $rows = DB::all("SELECT p.* FROM posts p WHERE $w ORDER BY p.published_at DESC, p.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $par);
        foreach ($rows as &$r) {
            $r['t'] = self::tr('post_translations', 'post_id', (int) $r['id']);
        }
        return $rows;
    }

    /** Categorías en árbol (padre → hijas) con el número de entradas publicadas. */
    public static function categories(): array
    {
        $now = now_site()->format('Y-m-d H:i:s');
        $counts = [];
        foreach (DB::all("SELECT pc.category_id AS id, COUNT(*) AS n FROM post_categories pc JOIN posts p ON p.id = pc.post_id WHERE p.status = 'published' AND p.published_at <= ? GROUP BY pc.category_id", [$now]) as $r) {
            $counts[(int) $r['id']] = (int) $r['n'];
        }
        $rows = DB::all('SELECT * FROM categories ORDER BY sort_order, id');
        $by = [];
        foreach ($rows as $r) {
            $t = self::tr('category_translations', 'category_id', (int) $r['id']);
            $by[(int) $r['id']] = $r + ['name' => ($t['name'] ?? '') ?: $r['slug'], 'count' => $counts[(int) $r['id']] ?? 0, 'children' => []];
        }
        $tree = [];
        foreach ($by as $id => &$c) {
            if ($c['parent_id'] && isset($by[(int) $c['parent_id']])) {
                $by[(int) $c['parent_id']]['children'][] = &$c;
            } else {
                $tree[] = &$c;
            }
        }
        unset($c);
        return array_values(array_filter($tree, static fn ($c) => $c['count'] > 0 || array_filter($c['children'], static fn ($x) => $x['count'] > 0)));
    }

    public static function category(string $slug): ?array
    {
        $c = DB::one('SELECT * FROM categories WHERE slug = ?', [$slug]);
        return $c ? $c + ['name' => (self::tr('category_translations', 'category_id', (int) $c['id'])['name'] ?? '') ?: $c['slug']] : null;
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
