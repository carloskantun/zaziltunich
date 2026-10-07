<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database as DB, View};
use App\Domain\{Catalog, Images};

final class ExperiencesController extends Base
{
    public const KINDS = ['slot' => 'Con horarios (tours, comidas, cenas)', 'lodging' => 'Hospedaje por noche', 'event' => 'Evento en fechas puntuales', 'quote' => 'Solo cotización / contacto'];
    public const MODELS = ['per_person' => 'Por persona', 'tiered' => 'Por persona con tarifas escalonadas', 'package' => 'Paquete (precio fijo por reserva)', 'per_night' => 'Por noche', 'variant_fixed' => 'Por variante (opciones con precio fijo)'];

    public function index(): void
    {
        $rows = DB::all(
            "SELECT e.*, t.title, (SELECT COUNT(*) FROM bookings b WHERE b.experience_id = e.id) AS n_bookings
             FROM experiences e LEFT JOIN experience_translations t ON t.experience_id = e.id AND t.lang = 'es' ORDER BY e.sort_order, e.id"
        );
        $this->page('experiences', ['title' => 'Experiencias', 'rows' => $rows], ['admin', 'operator', 'viewer']);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit(array $p): void
    {
        $exp = Catalog::load((int) $p['id']);
        if (!$exp) {
            View::notFound();
        }
        $this->form($exp);
    }

    private function form(?array $exp): void
    {
        $this->page('experience_form', [
            'title' => $exp ? 'Editar experiencia' : 'Nueva experiencia', 'exp' => $exp,
            'templates' => DB::all('SELECT t.*, (SELECT GROUP_CONCAT(x.time) FROM schedule_template_times x WHERE x.template_id = t.id) AS times FROM schedule_templates t ORDER BY t.name'),
            'allGroups' => DB::all('SELECT * FROM extra_groups ORDER BY name_es'),
            'capGroups' => DB::all('SELECT * FROM capacity_groups ORDER BY name'),
            'links' => $exp ? array_column(DB::all('SELECT group_id, required_override FROM experience_extra_groups WHERE experience_id = ?', [$exp['id']]), 'required_override', 'group_id') : [],
        ], ['admin']);
    }

    public function save(): void
    {
        $this->guard(['admin']);
        $id = (int) ($_POST['id'] ?? 0);
        $now = now_site()->format('Y-m-d H:i:s');
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', (string) ($_POST['slug'] ?: ($_POST['tr']['es']['title'] ?? ''))) ?: ''), '-'));
        if ($slug === '' || (string) $_POST['tr']['es']['title'] === '') {
            $this->back($id ? 'admin/experiencias/' . $id : 'admin/experiencias/nueva', 'Falta el título en español.', 'err');
        }
        if (DB::value('SELECT 1 FROM experiences WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $this->back($id ? 'admin/experiencias/' . $id : 'admin/experiencias/nueva', 'Ya existe otra experiencia con esa dirección (slug).', 'err');
        }
        $pick = static fn (string $k, array $allowed, string $def) => in_array($_POST[$k] ?? '', $allowed, true) ? (string) $_POST[$k] : $def;
        $int = static fn (string $k, int $def, int $min = 0) => max($min, (int) ($_POST[$k] ?? $def));
        $data = [
            'slug' => $slug,
            'kind' => $pick('kind', array_keys(self::KINDS), 'slot'),
            'status' => $pick('status', ['draft', 'published', 'archived'], 'draft'),
            'pricing_model' => $pick('pricing_model', array_keys(self::MODELS), 'per_person'),
            'base_price' => from_cents(to_cents($_POST['base_price'] ?? 0)),
            'currency' => $pick('currency', ['MXN', 'USD'], 'MXN'),
            'min_pax' => $int('min_pax', 1, 1), 'max_pax' => max($int('min_pax', 1, 1), $int('max_pax', 10, 1)),
            'lead_hours' => $int('lead_hours', 12), 'max_advance_days' => $int('max_advance_days', 365, 1),
            'payment_mode' => $pick('payment_mode', ['full', 'deposit'], 'full'),
            'deposit_percent' => number_format(max(0, min(100, (float) ($_POST['deposit_percent'] ?? 0))), 2, '.', ''),
            'capacity_group_id' => ((int) ($_POST['capacity_group_id'] ?? 0)) ?: null,
            'default_capacity' => $int('default_capacity', 20, 1),
            'min_nights' => $int('min_nights', 1, 1),
            'checkin_time' => preg_match('/^\d{2}:\d{2}$/', (string) ($_POST['checkin_time'] ?? '')) ? $_POST['checkin_time'] : null,
            'checkout_time' => preg_match('/^\d{2}:\d{2}$/', (string) ($_POST['checkout_time'] ?? '')) ? $_POST['checkout_time'] : null,
            'show_contact' => isset($_POST['show_contact']) ? 1 : 0,
            'sort_order' => $int('sort_order', 0),
            'updated_at' => $now,
        ];
        $hero = isset($_FILES['hero']) ? Images::store($_FILES['hero']) : null;
        if ($hero) {
            $data['hero_image'] = $hero;
        }
        $savedId = DB::tx(function () use ($id, $data, $now) {
            if ($id) {
                DB::update('experiences', $data, 'id = ?', [$id]);
            } else {
                $id = DB::insert('experiences', $data + ['created_at' => $now]);
            }
            $this->saveChildren($id);
            return $id;
        });
        $this->back('admin/experiencias/' . $savedId, 'Experiencia guardada.');
    }

    private function saveChildren(int $id): void
    {
        foreach (['es', 'en'] as $lang) {
            $t = (array) ($_POST['tr'][$lang] ?? []);
            $row = [];
            foreach (['title', 'subtitle', 'description', 'meeting_point', 'highlights', 'includes', 'excludes', 'bring', 'itinerary', 'faq', 'seo_title', 'seo_description'] as $f) {
                $row[$f] = trim((string) ($t[$f] ?? ''));
            }
            if (DB::value('SELECT 1 FROM experience_translations WHERE experience_id = ? AND lang = ?', [$id, $lang])) {
                DB::update('experience_translations', $row, 'experience_id = ? AND lang = ?', [$id, $lang]);
            } else {
                DB::insert('experience_translations', $row + ['experience_id' => $id, 'lang' => $lang]);
            }
        }
        foreach (['price_tiers', 'price_overrides', 'experience_schedules', 'event_dates', 'experience_extra_groups'] as $tbl) {
            DB::exec("DELETE FROM $tbl WHERE experience_id = ?", [$id]);
        }
        foreach ((array) ($_POST['tiers'] ?? []) as $r) {
            if (($r['price'] ?? '') === '' || ($r['min_pax'] ?? '') === '') {
                continue;
            }
            DB::insert('price_tiers', ['experience_id' => $id, 'min_pax' => (int) $r['min_pax'], 'max_pax' => ($r['max_pax'] ?? '') === '' ? null : (int) $r['max_pax'], 'price' => from_cents(to_cents($r['price']))]);
        }
        foreach ((array) ($_POST['overrides'] ?? []) as $r) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['date_from'] ?? '')) || ($r['price'] ?? '') === '') {
                continue;
            }
            $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['date_to'] ?? '')) ? $r['date_to'] : $r['date_from'];
            DB::insert('price_overrides', [
                'experience_id' => $id, 'label' => trim((string) ($r['label'] ?? '')), 'date_from' => $r['date_from'], 'date_to' => $to,
                'time' => preg_match('/^\d{2}:\d{2}$/', (string) ($r['time'] ?? '')) ? $r['time'] : null,
                'weekdays' => !empty($r['weekdays']) ? implode(',', array_map('intval', (array) $r['weekdays'])) : null,
                'price' => from_cents(to_cents($r['price'])),
            ]);
        }
        foreach ((array) ($_POST['schedules'] ?? []) as $r) {
            if (empty($r['template_id'])) {
                continue;
            }
            DB::insert('experience_schedules', [
                'experience_id' => $id, 'template_id' => (int) $r['template_id'],
                'weekdays' => !empty($r['weekdays']) ? implode(',', array_map('intval', (array) $r['weekdays'])) : '1,2,3,4,5,6,7',
                'valid_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['valid_from'] ?? '')) ? $r['valid_from'] : null,
                'valid_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['valid_to'] ?? '')) ? $r['valid_to'] : null,
                'capacity' => ($r['capacity'] ?? '') === '' ? null : max(1, (int) $r['capacity']),
            ]);
        }
        foreach ((array) ($_POST['events'] ?? []) as $r) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($r['event_date'] ?? ''))) {
                continue;
            }
            DB::insert('event_dates', ['experience_id' => $id, 'event_date' => $r['event_date'], 'time' => preg_match('/^\d{2}:\d{2}$/', (string) ($r['time'] ?? '')) ? $r['time'] : '19:00', 'capacity' => max(1, (int) ($r['capacity'] ?? 10))]);
        }
        // Variantes: se conservan los ids para no romper reservas existentes.
        $keep = [];
        foreach ((array) ($_POST['variants'] ?? []) as $i => $r) {
            if (trim((string) ($r['name_es'] ?? '')) === '') {
                continue;
            }
            $row = ['experience_id' => $id, 'name_es' => trim($r['name_es']), 'name_en' => trim((string) ($r['name_en'] ?? '')), 'price' => from_cents(to_cents($r['price'] ?? 0)), 'sort_order' => (int) $i];
            $vid = (int) ($r['id'] ?? 0);
            if ($vid && DB::value('SELECT 1 FROM variants WHERE id = ? AND experience_id = ?', [$vid, $id])) {
                DB::update('variants', $row, 'id = ?', [$vid]);
            } else {
                $vid = DB::insert('variants', $row);
            }
            $keep[] = $vid;
        }
        if ($keep) {
            DB::exec('DELETE FROM variants WHERE experience_id = ? AND id NOT IN (' . implode(',', array_map('intval', $keep)) . ')', [$id]);
        } else {
            DB::exec('DELETE FROM variants WHERE experience_id = ?', [$id]);
        }
        $n = 0;
        foreach ((array) ($_POST['extra_groups'] ?? []) as $gid => $r) {
            if (empty($r['on'])) {
                continue;
            }
            DB::insert('experience_extra_groups', [
                'experience_id' => $id, 'group_id' => (int) $gid,
                'required_override' => ($r['required'] ?? '') === '' ? null : (int) $r['required'], 'sort_order' => $n++,
            ]);
        }
    }

    public function duplicate(array $p): void
    {
        $this->guard(['admin']);
        $src = DB::one('SELECT * FROM experiences WHERE id = ?', [(int) $p['id']]);
        if (!$src) {
            View::notFound();
        }
        $newId = DB::tx(function () use ($src) {
            $slug = $src['slug'] . '-copia';
            for ($i = 2; DB::value('SELECT 1 FROM experiences WHERE slug = ?', [$slug]); $i++) {
                $slug = $src['slug'] . '-copia-' . $i;
            }
            $now = now_site()->format('Y-m-d H:i:s');
            $row = $src;
            unset($row['id']);
            $row = ['slug' => $slug, 'status' => 'draft', 'created_at' => $now, 'updated_at' => $now] + $row;
            $id = DB::insert('experiences', $row);
            $copy = static function (string $table) use ($src, $id): void {
                foreach (DB::all("SELECT * FROM $table WHERE experience_id = ?", [$src['id']]) as $r) {
                    unset($r['id']);
                    $r['experience_id'] = $id;
                    if ($table === 'experience_translations' && $r['lang'] === 'es') {
                        $r['title'] = 'Copia de ' . $r['title'];
                    }
                    DB::insert($table, $r);
                }
            };
            foreach (['experience_translations', 'price_tiers', 'price_overrides', 'variants', 'experience_schedules', 'event_dates', 'experience_extra_groups'] as $t) {
                $copy($t);
            }
            return $id;
        });
        $this->back('admin/experiencias/' . $newId, 'Duplicada como borrador. Ajusta título y dirección.');
    }

    public function delete(array $p): void
    {
        $this->guard(['admin']);
        $id = (int) $p['id'];
        if (DB::value('SELECT 1 FROM bookings WHERE experience_id = ?', [$id])) {
            DB::update('experiences', ['status' => 'archived'], 'id = ?', [$id]);
            $this->back('admin/experiencias', 'Tiene reservas, por eso se archivó en lugar de eliminarse.');
        }
        DB::exec('DELETE FROM experiences WHERE id = ?', [$id]);
        $this->back('admin/experiencias', 'Experiencia eliminada.');
    }
}
