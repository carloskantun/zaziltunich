<?php
declare(strict_types=1);

namespace App\Domain;

/** Normaliza la selección de un formulario de reserva. */
final class Selection
{
    public static function fromInput(array $in): array
    {
        $date = (string) ($in['date'] ?? '');
        $end = (string) ($in['end_date'] ?? '');
        $time = (string) ($in['time'] ?? '');
        return [
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
            'end_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) ? $end : '',
            'time' => preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '',
            'pax' => max(1, min(200, (int) ($in['pax'] ?? 1))),
            'units' => max(1, min(50, (int) ($in['units'] ?? 1))),
            'variant_id' => (int) ($in['variant_id'] ?? 0),
            'extras' => is_array($in['extras'] ?? null) ? $in['extras'] : [],
        ];
    }

    /** Campos ocultos para arrastrar la selección a la siguiente página. */
    public static function hidden(array $value, string $prefix = ''): string
    {
        $html = '';
        foreach ($value as $k => $v) {
            $name = $prefix === '' ? (string) $k : $prefix . '[' . $k . ']';
            if (is_array($v)) {
                $html .= self::hidden($v, $name);
            } elseif ($v !== '' && $v !== null) {
                $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($v) . '">';
            }
        }
        return $html;
    }
}
