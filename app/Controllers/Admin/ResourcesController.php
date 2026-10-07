<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database as DB;

/** Recursos reutilizables: plantillas de horarios, grupos de extras, bloqueos y capacidad compartida. */
final class ResourcesController extends Base
{
    // ---------- Horarios ----------
    public function templates(): void
    {
        $rows = DB::all('SELECT * FROM schedule_templates ORDER BY name');
        foreach ($rows as &$r) {
            $r['times'] = implode(', ', array_column(DB::all('SELECT time FROM schedule_template_times WHERE template_id = ? ORDER BY time', [$r['id']]), 'time'));
            $r['used'] = (int) DB::value('SELECT COUNT(*) FROM experience_schedules WHERE template_id = ?', [$r['id']]);
        }
        $this->page('templates', ['title' => 'Plantillas de horarios', 'rows' => $rows], ['admin']);
    }

    public function saveTemplates(): void
    {
        $this->guard(['admin']);
        DB::tx(function () {
            $keep = [];
            foreach ((array) ($_POST['tpl'] ?? []) as $r) {
                $name = trim((string) ($r['name'] ?? ''));
                preg_match_all('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', (string) ($r['times'] ?? ''), $m, PREG_SET_ORDER);
                $times = array_values(array_unique(array_map(static fn ($x) => sprintf('%02d:%s', (int) $x[1], $x[2]), $m)));
                if ($name === '' || $times === []) {
                    continue;
                }
                $id = (int) ($r['id'] ?? 0);
                if ($id && DB::value('SELECT 1 FROM schedule_templates WHERE id = ?', [$id])) {
                    DB::update('schedule_templates', ['name' => $name], 'id = ?', [$id]);
                    DB::exec('DELETE FROM schedule_template_times WHERE template_id = ?', [$id]);
                } else {
                    $id = DB::insert('schedule_templates', ['name' => $name]);
                }
                foreach ($times as $t) {
                    DB::insert('schedule_template_times', ['template_id' => $id, 'time' => $t]);
                }
                $keep[] = $id;
            }
            foreach (DB::all('SELECT id FROM schedule_templates') as $t) {
                if (!in_array((int) $t['id'], $keep, true) && !DB::value('SELECT 1 FROM experience_schedules WHERE template_id = ?', [$t['id']])) {
                    DB::exec('DELETE FROM schedule_templates WHERE id = ?', [$t['id']]);
                }
            }
        });
        $this->back('admin/horarios', 'Plantillas guardadas (las que están en uso no se eliminan).');
    }

    // ---------- Extras ----------
    public function extras(): void
    {
        $groups = DB::all('SELECT * FROM extra_groups ORDER BY sort_order, id');
        foreach ($groups as &$g) {
            $g['options'] = DB::all('SELECT * FROM extra_options WHERE group_id = ? ORDER BY sort_order, id', [$g['id']]);
            $g['used'] = (int) DB::value('SELECT COUNT(*) FROM experience_extra_groups WHERE group_id = ?', [$g['id']]);
        }
        $this->page('extras', ['title' => 'Grupos de extras', 'groups' => $groups], ['admin']);
    }

    public function saveExtras(): void
    {
        $this->guard(['admin']);
        DB::tx(function () {
            $keepG = [];
            foreach (array_values((array) ($_POST['groups'] ?? [])) as $gi => $g) {
                if (trim((string) ($g['name_es'] ?? '')) === '') {
                    continue;
                }
                $row = [
                    'name_es' => trim($g['name_es']), 'name_en' => trim((string) ($g['name_en'] ?? '')),
                    'help_es' => trim((string) ($g['help_es'] ?? '')), 'help_en' => trim((string) ($g['help_en'] ?? '')),
                    'selection' => in_array($g['selection'] ?? '', ['list', 'checkbox', 'quantity'], true) ? $g['selection'] : 'list',
                    'mode' => ($g['mode'] ?? '') === 'auto' ? 'auto' : 'manual',
                    'required' => !empty($g['required']) ? 1 : 0, 'sort_order' => $gi,
                ];
                $gid = (int) ($g['id'] ?? 0);
                if ($gid && DB::value('SELECT 1 FROM extra_groups WHERE id = ?', [$gid])) {
                    DB::update('extra_groups', $row, 'id = ?', [$gid]);
                } else {
                    $gid = DB::insert('extra_groups', $row);
                }
                $keepG[] = $gid;
                $keepO = [];
                foreach (array_values((array) ($g['options'] ?? [])) as $oi => $o) {
                    if (trim((string) ($o['label_es'] ?? '')) === '') {
                        continue;
                    }
                    $nullInt = static fn ($v) => ($v ?? '') === '' ? null : (int) $v;
                    $orow = [
                        'group_id' => $gid, 'label_es' => trim($o['label_es']), 'label_en' => trim((string) ($o['label_en'] ?? '')),
                        'charge' => in_array($o['charge'] ?? '', ['fixed', 'per_person', 'per_unit'], true) ? $o['charge'] : 'fixed',
                        'amount' => from_cents(to_cents($o['amount'] ?? 0)),
                        'pax_min' => $nullInt($o['pax_min'] ?? null), 'pax_max' => $nullInt($o['pax_max'] ?? null), 'max_qty' => $nullInt($o['max_qty'] ?? null),
                        'sort_order' => $oi,
                    ];
                    $oid = (int) ($o['id'] ?? 0);
                    if ($oid && DB::value('SELECT 1 FROM extra_options WHERE id = ? AND group_id = ?', [$oid, $gid])) {
                        DB::update('extra_options', $orow, 'id = ?', [$oid]);
                    } else {
                        $oid = DB::insert('extra_options', $orow);
                    }
                    $keepO[] = $oid;
                }
                DB::exec('DELETE FROM extra_options WHERE group_id = ?' . ($keepO ? ' AND id NOT IN (' . implode(',', $keepO) . ')' : ''), [$gid]);
            }
            DB::exec('DELETE FROM extra_groups' . ($keepG ? ' WHERE id NOT IN (' . implode(',', $keepG) . ')' : ''));
        });
        $this->back('admin/extras', 'Extras guardados.');
    }

    // ---------- Bloqueos y capacidad compartida ----------
    public function blocks(): void
    {
        $this->page('blocks', [
            'title' => 'Bloqueos y capacidad compartida',
            'blocks' => DB::all("SELECT b.*, t.title, g.name AS group_name FROM blocks b
                LEFT JOIN experience_translations t ON t.experience_id = b.experience_id AND t.lang = 'es'
                LEFT JOIN capacity_groups g ON g.id = b.capacity_group_id ORDER BY b.date_from DESC LIMIT 200"),
            'experiences' => DB::all("SELECT e.id, t.title FROM experiences e LEFT JOIN experience_translations t ON t.experience_id = e.id AND t.lang = 'es' ORDER BY e.sort_order, e.id"),
            'groups' => DB::all('SELECT g.*, (SELECT COUNT(*) FROM experiences e WHERE e.capacity_group_id = g.id) AS n FROM capacity_groups g ORDER BY g.name'),
        ], ['admin']);
    }

    public function saveBlocks(): void
    {
        $this->guard(['admin']);
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'add_block') {
            $from = (string) ($_POST['date_from'] ?? '');
            $to = (string) ($_POST['date_to'] ?? '') ?: $from;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) {
                $this->back('admin/bloqueos', 'Fechas inválidas.', 'err');
            }
            $target = (string) ($_POST['target'] ?? '');
            DB::insert('blocks', [
                'experience_id' => str_starts_with($target, 'e') ? (int) substr($target, 1) : null,
                'capacity_group_id' => str_starts_with($target, 'g') ? (int) substr($target, 1) : null,
                'date_from' => $from, 'date_to' => $to,
                'time' => preg_match('/^\d{2}:\d{2}$/', (string) ($_POST['time'] ?? '')) ? $_POST['time'] : null,
                'reason' => trim((string) ($_POST['reason'] ?? '')),
            ]);
            $this->back('admin/bloqueos', 'Bloqueo creado.');
        }
        if ($action === 'del_block') {
            DB::exec('DELETE FROM blocks WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
            $this->back('admin/bloqueos', 'Bloqueo eliminado.');
        }
        if ($action === 'save_group') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name !== '') {
                $id = (int) ($_POST['id'] ?? 0);
                $row = ['name' => $name, 'capacity' => max(1, (int) ($_POST['capacity'] ?? 20))];
                $id ? DB::update('capacity_groups', $row, 'id = ?', [$id]) : DB::insert('capacity_groups', $row);
            }
            $this->back('admin/bloqueos', 'Grupo guardado.');
        }
        if ($action === 'del_group') {
            $id = (int) ($_POST['id'] ?? 0);
            DB::exec('UPDATE experiences SET capacity_group_id = NULL WHERE capacity_group_id = ?', [$id]);
            DB::exec('DELETE FROM capacity_groups WHERE id = ?', [$id]);
            $this->back('admin/bloqueos', 'Grupo eliminado.');
        }
        $this->back('admin/bloqueos');
    }
}
