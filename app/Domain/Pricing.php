<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * Motor de precios puro (sin base de datos): recibe la experiencia ya cargada y una selección,
 * devuelve desglose y totales en centavos. El servidor es la autoridad; el navegador solo muestra.
 */
final class Pricing
{
    /**
     * @param array $exp   Resultado de Catalog::load()
     * @param array $in    date, time, pax, units, variant_id, end_date, extras[group_id => valor]
     * @return array{ok:bool, errors:array, base:int, extras_total:int, total:int, deposit:int, nights:?int, lines:array, extras:array, unit_price:int}
     */
    public static function quote(array $exp, array $in): array
    {
        $errors = [];
        $kind = $exp['kind'];
        $pax = max(1, (int) ($in['pax'] ?? 1));
        $units = max(1, (int) ($in['units'] ?? 1));
        $date = (string) ($in['date'] ?? '');
        $time = isset($in['time']) && $in['time'] !== '' ? (string) $in['time'] : null;
        $nights = null;

        if ($kind === 'lodging') {
            $endDate = (string) ($in['end_date'] ?? '');
            $nights = self::nightsBetween($date, $endDate);
            if ($nights < 1) {
                $errors[] = ['code' => 'err.generic'];
                $nights = 0;
            } elseif ($nights < $exp['min_nights']) {
                $errors[] = ['code' => 'err.nights_min', 'vars' => ['n' => $exp['min_nights']]];
            }
        }
        if ($pax < $exp['min_pax'] || $pax > $exp['max_pax']) {
            $errors[] = ['code' => 'err.pax_range', 'vars' => ['min' => $exp['min_pax'], 'max' => $exp['max_pax']]];
        }

        $lines = [];
        $unit = self::unitPrice($exp, $date, $time, $pax, (int) ($in['variant_id'] ?? 0));
        $base = 0;
        switch ($exp['pricing_model']) {
            case 'per_person':
            case 'tiered':
                $base = $unit * $pax;
                $lines[] = ['label' => $pax . ' × ' . money($unit, $exp['currency']), 'amount' => $base];
                break;
            case 'package':
                $base = $unit * $units;
                $lines[] = ['label' => ($units > 1 ? $units . ' × ' : '') . money($unit, $exp['currency']), 'amount' => $base];
                break;
            case 'per_night':
                $n = (int) $nights;
                $base = $unit * $n * $units;
                $lines[] = ['label' => $n . ' × ' . money($unit, $exp['currency']) . ($units > 1 ? ' × ' . $units : ''), 'amount' => $base];
                break;
            case 'variant_fixed':
                $base = $unit * $units;
                $lines[] = ['label' => ($units > 1 ? $units . ' × ' : '') . money($unit, $exp['currency']), 'amount' => $base];
                if ($unit === 0) {
                    $errors[] = ['code' => 'err.generic'];
                }
                break;
        }

        [$extraLines, $extrasTotal, $extraErrors] = self::extras($exp, (array) ($in['extras'] ?? []), $pax);
        $errors = array_merge($errors, $extraErrors);

        $total = $base + $extrasTotal;
        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'unit_price' => $unit,
            'base' => $base,
            'extras_total' => $extrasTotal,
            'total' => $total,
            'deposit' => self::deposit($exp, $total),
            'nights' => $nights,
            'lines' => $lines,
            'extras' => $extraLines,
        ];
    }

    public static function deposit(array $exp, int $total): int
    {
        if (($exp['payment_mode'] ?? 'full') !== 'deposit') {
            return $total;
        }
        $bp = (int) $exp['deposit_bp'];
        $bp = max(0, min(10000, $bp));
        return intdiv($total * $bp + 5000, 10000);
    }

    /** Precio unitario según modelo, sobrescrituras por fecha/hora y variante. */
    public static function unitPrice(array $exp, string $date, ?string $time, int $pax, int $variantId = 0): int
    {
        if ($exp['pricing_model'] === 'variant_fixed') {
            foreach ($exp['variants'] as $v) {
                if ($v['id'] === $variantId) {
                    return $v['price'];
                }
            }
            return 0;
        }
        $override = self::override($exp, $date, $time);
        if ($override !== null) {
            return $override;
        }
        if ($exp['pricing_model'] === 'tiered') {
            foreach ($exp['tiers'] as $t) {
                if ($pax >= $t['min_pax'] && ($t['max_pax'] === null || $pax <= $t['max_pax'])) {
                    return $t['price'];
                }
            }
        }
        return $exp['base_price'];
    }

    /** Sobrescritura más específica (con hora gana a sin hora; luego la última por fecha de inicio). */
    public static function override(array $exp, string $date, ?string $time): ?int
    {
        if ($date === '') {
            return null;
        }
        $dow = (string) date('N', strtotime($date . ' 12:00:00'));
        $best = null;
        $bestScore = -1;
        foreach ($exp['overrides'] as $o) {
            if ($date < $o['date_from'] || $date > $o['date_to']) {
                continue;
            }
            if ($o['weekdays'] !== null && $o['weekdays'] !== '' && !in_array($dow, explode(',', $o['weekdays']), true)) {
                continue;
            }
            if ($o['time'] !== null && $o['time'] !== '' && $o['time'] !== $time) {
                continue;
            }
            $score = ($o['time'] ? 2 : 0) + ($o['weekdays'] ? 1 : 0);
            if ($score >= $bestScore) {
                $best = $o['price'];
                $bestScore = $score;
            }
        }
        return $best;
    }

    public static function nightsBetween(string $from, string $to): int
    {
        $a = strtotime($from . ' 12:00:00');
        $b = strtotime($to . ' 12:00:00');
        if ($a === false || $b === false) {
            return 0;
        }
        return (int) round(($b - $a) / 86400);
    }

    /** Opciones válidas de un grupo para cierto número de personas. */
    public static function optionsFor(array $group, int $pax): array
    {
        return array_values(array_filter($group['options'], static function ($o) use ($pax) {
            return ($o['pax_min'] === null || $pax >= $o['pax_min']) && ($o['pax_max'] === null || $pax <= $o['pax_max']);
        }));
    }

    private static function charge(array $o, int $pax, int $qty): int
    {
        return match ($o['charge']) {
            'per_person' => $o['amount'] * $pax,
            'per_unit' => $o['amount'] * $qty,
            default => $o['amount'],
        };
    }

    /** @return array{0:array,1:int,2:array} líneas, total, errores */
    private static function extras(array $exp, array $input, int $pax): array
    {
        $lines = [];
        $total = 0;
        $errors = [];
        foreach ($exp['extra_groups'] as $g) {
            $gid = $g['id'];
            $raw = $input[$gid] ?? null;
            $name = $g['name_' . \App\Core\I18n::lang()] ?: $g['name_es'];
            $valid = self::optionsFor($g, $pax);
            $byId = [];
            foreach ($valid as $o) {
                $byId[$o['id']] = $o;
            }
            $label = static fn (array $o): string => (\App\Core\I18n::lang() === 'en' && $o['label_en'] !== '') ? $o['label_en'] : $o['label_es'];
            $picked = [];

            if ($g['mode'] === 'auto') {
                // Selección automática por número de personas; el cliente solo decide incluir o no.
                $include = $g['required'] ? true : in_array((string) $raw, ['1', 'on', 'true'], true);
                if ($include) {
                    if ($valid === []) {
                        $errors[] = ['code' => 'err.extra_unavailable', 'vars' => ['name' => $name, 'pax' => $pax]];
                    } else {
                        $picked[] = [$valid[0], 1];
                    }
                }
            } elseif ($g['selection'] === 'list') {
                $id = (int) (is_array($raw) ? 0 : $raw);
                if ($id > 0) {
                    if (isset($byId[$id])) {
                        $picked[] = [$byId[$id], 1];
                    } else {
                        $errors[] = ['code' => 'err.extra_unavailable', 'vars' => ['name' => $name, 'pax' => $pax]];
                    }
                } elseif ($g['required']) {
                    $errors[] = ['code' => 'err.extra_required', 'vars' => ['name' => $name]];
                }
            } elseif ($g['selection'] === 'checkbox') {
                foreach ((array) $raw as $id) {
                    $id = (int) $id;
                    if (isset($byId[$id])) {
                        $picked[] = [$byId[$id], 1];
                    }
                }
                if ($g['required'] && $picked === []) {
                    $errors[] = ['code' => 'err.extra_required', 'vars' => ['name' => $name]];
                }
            } else { // quantity
                foreach ((array) $raw as $id => $qty) {
                    $id = (int) $id;
                    $qty = max(0, (int) $qty);
                    if ($qty > 0 && isset($byId[$id])) {
                        $max = $byId[$id]['max_qty'];
                        $picked[] = [$byId[$id], $max !== null ? min($qty, $max) : $qty];
                    }
                }
                if ($g['required'] && $picked === []) {
                    $errors[] = ['code' => 'err.extra_required', 'vars' => ['name' => $name]];
                }
            }

            foreach ($picked as [$o, $qty]) {
                $amount = self::charge($o, $pax, $qty);
                $total += $amount;
                $lines[] = [
                    'group_id' => $gid,
                    'option_id' => $o['id'],
                    'label' => $name . ': ' . $label($o) . ($qty > 1 ? ' × ' . $qty : ''),
                    'qty' => $qty,
                    'amount' => $amount,
                ];
            }
        }
        return [$lines, $total, $errors];
    }
}
