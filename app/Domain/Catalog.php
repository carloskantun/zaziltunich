<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database as DB;

/** Lectura del catálogo: experiencias con precios, horarios, variantes y extras (dinero en centavos). */
final class Catalog
{
    public static function published(): array
    {
        $rows = DB::all("SELECT id FROM experiences WHERE status = 'published' ORDER BY sort_order, id");
        return array_map(static fn ($r) => self::load((int) $r['id']), $rows);
    }

    public static function bySlug(string $slug, bool $onlyPublished = true): ?array
    {
        $row = DB::one('SELECT id, status FROM experiences WHERE slug = ?', [$slug]);
        if (!$row || ($onlyPublished && $row['status'] !== 'published')) {
            return null;
        }
        return self::load((int) $row['id']);
    }

    public static function load(int $id): ?array
    {
        $e = DB::one('SELECT * FROM experiences WHERE id = ?', [$id]);
        if (!$e) {
            return null;
        }
        foreach (['base_price'] as $k) {
            $e[$k] = to_cents($e[$k]);
        }
        foreach (['id', 'min_pax', 'max_pax', 'lead_hours', 'max_advance_days', 'default_capacity', 'min_nights', 'sort_order'] as $k) {
            $e[$k] = (int) $e[$k];
        }
        $e['deposit_bp'] = (int) round(((float) $e['deposit_percent']) * 100);
        $e['tr'] = [];
        foreach (DB::all('SELECT * FROM experience_translations WHERE experience_id = ?', [$id]) as $t) {
            $e['tr'][$t['lang']] = $t;
        }
        $e['tiers'] = array_map(static function ($r) {
            return ['min_pax' => (int) $r['min_pax'], 'max_pax' => $r['max_pax'] === null ? null : (int) $r['max_pax'], 'price' => to_cents($r['price'])];
        }, DB::all('SELECT * FROM price_tiers WHERE experience_id = ? ORDER BY min_pax', [$id]));
        $e['overrides'] = array_map(static function ($r) {
            $r['price'] = to_cents($r['price']);
            return $r;
        }, DB::all('SELECT * FROM price_overrides WHERE experience_id = ? ORDER BY date_from', [$id]));
        $e['variants'] = array_map(static function ($r) {
            $r['id'] = (int) $r['id'];
            $r['price'] = to_cents($r['price']);
            return $r;
        }, DB::all('SELECT * FROM variants WHERE experience_id = ? ORDER BY sort_order, id', [$id]));
        $e['schedules'] = [];
        foreach (DB::all('SELECT * FROM experience_schedules WHERE experience_id = ? ORDER BY id', [$id]) as $s) {
            $s['times'] = array_column(DB::all('SELECT time FROM schedule_template_times WHERE template_id = ? ORDER BY time', [(int) $s['template_id']]), 'time');
            $e['schedules'][] = $s;
        }
        $e['event_dates'] = DB::all('SELECT * FROM event_dates WHERE experience_id = ? ORDER BY event_date, time', [$id]);
        $e['extra_groups'] = self::extraGroups($id);
        return $e;
    }

    public static function extraGroups(int $experienceId): array
    {
        $groups = DB::all(
            'SELECT g.*, l.required_override, l.sort_order AS link_order FROM extra_groups g
             JOIN experience_extra_groups l ON l.group_id = g.id WHERE l.experience_id = ?
             ORDER BY l.sort_order, g.sort_order, g.id',
            [$experienceId]
        );
        foreach ($groups as &$g) {
            $g['id'] = (int) $g['id'];
            $g['required'] = $g['required_override'] !== null ? (int) $g['required_override'] : (int) $g['required'];
            $g['options'] = array_map(static function ($o) {
                $o['id'] = (int) $o['id'];
                $o['amount'] = to_cents($o['amount']);
                $o['pax_min'] = $o['pax_min'] === null ? null : (int) $o['pax_min'];
                $o['pax_max'] = $o['pax_max'] === null ? null : (int) $o['pax_max'];
                $o['max_qty'] = $o['max_qty'] === null ? null : (int) $o['max_qty'];
                return $o;
            }, DB::all('SELECT * FROM extra_options WHERE group_id = ? ORDER BY sort_order, id', [$g['id']]));
        }
        return $groups;
    }

    /** Texto traducido con respaldo en español. */
    public static function text(array $e, string $field, ?string $lang = null): string
    {
        $lang = $lang ?? \App\Core\I18n::lang();
        $v = trim((string) ($e['tr'][$lang][$field] ?? ''));
        if ($v === '') {
            $v = trim((string) ($e['tr']['es'][$field] ?? ''));
        }
        return $v;
    }

    /** Precio "desde" en centavos para tarjetas. */
    public static function fromPrice(array $e): int
    {
        switch ($e['pricing_model']) {
            case 'tiered':
                $prices = array_column($e['tiers'], 'price');
                return $prices ? min($prices) : $e['base_price'];
            case 'variant_fixed':
                $prices = array_column($e['variants'], 'price');
                return $prices ? min($prices) : $e['base_price'];
            default:
                return $e['base_price'];
        }
    }

    public static function priceSuffix(array $e): string
    {
        return match ($e['pricing_model']) {
            'per_person', 'tiered' => '/ ' . (\App\Core\I18n::lang() === 'en' ? 'person' : 'persona'),
            'per_night' => '/ ' . (\App\Core\I18n::lang() === 'en' ? 'night' : 'noche'),
            default => '',
        };
    }
}
