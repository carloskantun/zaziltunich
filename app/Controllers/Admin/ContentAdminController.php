<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database as DB, View};
use App\Domain\{Content, Images};

/** Editor de páginas y entradas de blog (ES/EN). El contenido es HTML escrito por administradores. */
final class ContentAdminController extends Base
{
    private function slugify(string $s): string
    {
        $s = (string) iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'));
    }

    // ---------- Páginas ----------
    public function pages(): void
    {
        $rows = DB::all("SELECT p.*, t.title FROM pages p LEFT JOIN page_translations t ON t.page_id = p.id AND t.lang = 'es' ORDER BY p.sort_order, p.id");
        $this->page('pages', ['title' => 'Páginas', 'rows' => $rows], ['admin', 'operator', 'viewer']);
    }

    public function pageForm(array $p = []): void
    {
        $id = (int) ($p['id'] ?? 0);
        $row = $id ? DB::one('SELECT * FROM pages WHERE id = ?', [$id]) : null;
        if ($id && !$row) {
            View::notFound();
        }
        $tr = [];
        if ($row) {
            foreach (DB::all('SELECT * FROM page_translations WHERE page_id = ?', [$id]) as $t) {
                $tr[$t['lang']] = $t;
            }
        }
        $this->page('page_form', ['title' => $row ? 'Editar página' : 'Nueva página', 'row' => $row, 'tr' => $tr], ['admin']);
    }

    public function pageNew(): void
    {
        $this->pageForm([]);
    }

    public function pageSave(): void
    {
        $this->guard(['admin']);
        $id = (int) ($_POST['id'] ?? 0);
        $slug = $this->slugify((string) ($_POST['slug'] ?: ($_POST['tr']['es']['title'] ?? '')));
        if ($slug === '' || in_array($slug, ['admin', 'api', 'blog', 'reservaciones', 'install', 'assets', 'uploads', 'pago', 'gracias', 'voucher', 'checkout', 'en'], true)) {
            $this->back($id ? 'admin/paginas/' . $id : 'admin/paginas/nueva', 'Dirección (slug) inválida o reservada.', 'err');
        }
        if (DB::value('SELECT 1 FROM pages WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $this->back($id ? 'admin/paginas/' . $id : 'admin/paginas/nueva', 'Ya existe una página con esa dirección.', 'err');
        }
        $data = [
            'slug' => $slug, 'status' => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
            'dark' => isset($_POST['dark']) ? 1 : 0, 'show_in_nav' => isset($_POST['show_in_nav']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0), 'updated_at' => now_site()->format('Y-m-d H:i:s'),
        ];
        $img = isset($_FILES['hero']) ? Images::store($_FILES['hero']) : null;
        if ($img) {
            $data['hero_image'] = $img;
        }
        $saved = DB::tx(function () use ($id, $data) {
            $id ? DB::update('pages', $data, 'id = ?', [$id]) : $id = DB::insert('pages', $data);
            foreach (['es', 'en'] as $l) {
                $t = (array) ($_POST['tr'][$l] ?? []);
                $row = ['title' => trim((string) ($t['title'] ?? '')), 'nav_label' => trim((string) ($t['nav_label'] ?? '')), 'content' => (string) ($t['content'] ?? ''),
                    'seo_title' => trim((string) ($t['seo_title'] ?? '')), 'seo_description' => trim((string) ($t['seo_description'] ?? ''))];
                if (DB::value('SELECT 1 FROM page_translations WHERE page_id = ? AND lang = ?', [$id, $l])) {
                    DB::update('page_translations', $row, 'page_id = ? AND lang = ?', [$id, $l]);
                } else {
                    DB::insert('page_translations', $row + ['page_id' => $id, 'lang' => $l]);
                }
            }
            return $id;
        });
        $this->back('admin/paginas/' . $saved, 'Página guardada.');
    }

    public function pageDelete(array $p): void
    {
        $this->guard(['admin']);
        DB::exec('DELETE FROM pages WHERE id = ?', [(int) $p['id']]);
        $this->back('admin/paginas', 'Página eliminada.');
    }

    // ---------- Blog ----------
    public function posts(): void
    {
        $rows = DB::all("SELECT p.*, t.title FROM posts p LEFT JOIN post_translations t ON t.post_id = p.id AND t.lang = 'es' ORDER BY p.published_at DESC");
        $this->page('posts', ['title' => 'Blog', 'rows' => $rows], ['admin', 'operator', 'viewer']);
    }

    public function postNew(): void
    {
        $this->postEdit([]);
    }

    public function postEdit(array $p): void
    {
        $id = (int) ($p['id'] ?? 0);
        $row = $id ? DB::one('SELECT * FROM posts WHERE id = ?', [$id]) : null;
        if ($id && !$row) {
            View::notFound();
        }
        $tr = [];
        if ($row) {
            foreach (DB::all('SELECT * FROM post_translations WHERE post_id = ?', [$id]) as $t) {
                $tr[$t['lang']] = $t;
            }
        }
        $this->page('post_form', ['title' => $row ? 'Editar entrada' : 'Nueva entrada', 'row' => $row, 'tr' => $tr], ['admin']);
    }

    public function postSave(): void
    {
        $this->guard(['admin']);
        $id = (int) ($_POST['id'] ?? 0);
        $slug = $this->slugify((string) ($_POST['slug'] ?: ($_POST['tr']['es']['title'] ?? '')));
        if ($slug === '') {
            $this->back($id ? 'admin/blog/' . $id : 'admin/blog/nueva', 'Falta el título.', 'err');
        }
        if (DB::value('SELECT 1 FROM posts WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $this->back($id ? 'admin/blog/' . $id : 'admin/blog/nueva', 'Ya existe una entrada con esa dirección.', 'err');
        }
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['published_at'] ?? '')) ? $_POST['published_at'] . ' 08:00:00' : now_site()->format('Y-m-d H:i:s');
        $data = ['slug' => $slug, 'status' => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published', 'published_at' => $date, 'updated_at' => now_site()->format('Y-m-d H:i:s')];
        $img = isset($_FILES['image']) ? Images::store($_FILES['image']) : null;
        if ($img) {
            $data['image'] = $img;
        }
        $saved = DB::tx(function () use ($id, $data) {
            $id ? DB::update('posts', $data, 'id = ?', [$id]) : $id = DB::insert('posts', $data);
            foreach (['es', 'en'] as $l) {
                $t = (array) ($_POST['tr'][$l] ?? []);
                $row = ['title' => trim((string) ($t['title'] ?? '')), 'excerpt' => trim((string) ($t['excerpt'] ?? '')), 'content' => (string) ($t['content'] ?? ''),
                    'seo_title' => trim((string) ($t['seo_title'] ?? '')), 'seo_description' => trim((string) ($t['seo_description'] ?? ''))];
                if (DB::value('SELECT 1 FROM post_translations WHERE post_id = ? AND lang = ?', [$id, $l])) {
                    DB::update('post_translations', $row, 'post_id = ? AND lang = ?', [$id, $l]);
                } else {
                    DB::insert('post_translations', $row + ['post_id' => $id, 'lang' => $l]);
                }
            }
            return $id;
        });
        $this->back('admin/blog/' . $saved, 'Entrada guardada.');
    }

    public function postDelete(array $p): void
    {
        $this->guard(['admin']);
        DB::exec('DELETE FROM posts WHERE id = ?', [(int) $p['id']]);
        $this->back('admin/blog', 'Entrada eliminada.');
    }
}
