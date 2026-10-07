<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Database as DB;

/** Datos iniciales: catálogo actual de Zazil Tunich, plantillas de horario y extras. Precios en MXN. */
final class Seeder
{
    public static function admin(string $name, string $email, string $password): void
    {
        DB::insert('users', [
            'name' => $name, 'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin', 'active' => 1, 'created_at' => now_site()->format('Y-m-d H:i:s'),
        ]);
    }

    public static function settings(): void
    {
        $defaults = [
            'site_name' => 'Zazil Tunich',
            'contact_whatsapp' => '', 'contact_phone' => '', 'contact_email' => '', 'contact_sms' => '',
            'hold_minutes' => '1440',
            'payment_instructions_es' => '', 'payment_instructions_en' => '',
        ];
        foreach ($defaults as $k => $v) {
            Settings::set($k, $v);
        }
    }

    public static function catalog(): void
    {
        $now = now_site()->format('Y-m-d H:i:s');

        $tpl = [];
        foreach (['Mañana (12:00, 13:00)' => ['12:00', '13:00'], 'Tarde (15:00, 17:00, 19:00)' => ['15:00', '17:00', '19:00'],
                  'Comida (11:00 a 19:00)' => ['11:00', '13:00', '15:00', '17:00', '19:00'], 'Cena (17:00, 19:00)' => ['17:00', '19:00']] as $name => $times) {
            $id = DB::insert('schedule_templates', ['name' => $name]);
            foreach ($times as $t) {
                DB::insert('schedule_template_times', ['template_id' => $id, 'time' => $t]);
            }
            $tpl[$name] = $id;
        }
        [$morning, $afternoon, $lunch, $dinner] = array_values($tpl);

        $group = static function (string $es, string $en, string $selection, int $required, string $mode, array $options, string $help = '') {
            $gid = DB::insert('extra_groups', [
                'name_es' => $es, 'name_en' => $en, 'help_es' => $help, 'help_en' => '', 'selection' => $selection,
                'required' => $required, 'mode' => $mode, 'sort_order' => 0,
            ]);
            foreach ($options as $i => $o) {
                DB::insert('extra_options', [
                    'group_id' => $gid, 'label_es' => $o[0], 'label_en' => $o[1], 'charge' => $o[2], 'amount' => from_cents($o[3] * 100),
                    'pax_min' => $o[4] ?? null, 'pax_max' => $o[5] ?? null, 'max_qty' => $o[6] ?? null, 'sort_order' => $i,
                ]);
            }
            return $gid;
        };
        $donation = $group('Donación a la comunidad', 'Community donation', 'list', 1, 'manual', [
            ['No donar', 'No donation', 'fixed', 0], ['$50', '$50', 'fixed', 50], ['$100', '$100', 'fixed', 100],
            ['$200', '$200', 'fixed', 200], ['$500', '$500', 'fixed', 500],
        ]);
        $transport = $group('Transporte', 'Transport', 'list', 0, 'auto', [
            ['1 a 4 personas', '1 to 4 guests', 'fixed', 800, 1, 4], ['5 a 6 personas', '5 to 6 guests', 'fixed', 1200, 5, 6],
            ['7 personas', '7 guests', 'fixed', 1400, 7, 7], ['8 personas', '8 guests', 'fixed', 1600, 8, 8],
            ['9 personas', '9 guests', 'fixed', 1800, 9, 9], ['10 personas', '10 guests', 'fixed', 2000, 10, 10],
        ], 'Tarifa automática según el número de personas.');
        $meal = $group('Comida tradicional', 'Traditional meal', 'quantity', 0, 'manual', [
            ['Comida tradicional (por persona)', 'Traditional meal (per guest)', 'per_unit', 350, null, null, 20],
        ]);
        $proposal = $group('Arreglos para la pedida', 'Proposal decorations', 'checkbox', 0, 'manual', [
            ['Arreglos para pedida', 'Proposal decorations', 'fixed', 9500],
        ]);

        // slug, es, en, kind, model, price, min, max, template, extras[groupIds], extra
        $items = [
            ['cenote-museo', 'Cenote Museo (mañana)', 'Museum Cenote (morning)', 'per_person', 400, 1, 10, $morning, [$donation, $transport, $meal]],
            ['inframundo-maya', 'Inframundo maya (tarde)', 'Mayan Underworld (afternoon)', 'per_person', 400, 1, 10, $afternoon, [$donation, $transport]],
            ['comida-inframundo', 'Comida en el Inframundo', 'Lunch in the Underworld', 'per_person', 1500, 1, 10, $lunch, [$donation]],
            ['noches-de-xibalba', 'Xibalbá con comida', 'Xibalbá with dinner', 'per_person', 750, 1, 10, $afternoon, [$donation, $transport]],
            ['comida-en-cenote-huinik', 'Comida en Cenote Huinik', 'Lunch at Huinik Cenote', 'per_person', 1500, 1, 10, $afternoon, [$donation, $transport]],
            ['comida-en-cenote-dzul', 'Comida en Cenote Dzul', 'Lunch at Dzul Cenote', 'per_person', 1800, 1, 10, $afternoon, [$donation, $transport]],
            ['cena-romantica-huinik', 'Cena romántica en Huinik', 'Romantic dinner at Huinik', 'per_person', 1700, 2, 10, $dinner, [$donation, $transport]],
            ['cena-romantica-dzul', 'Cena romántica en Dzul', 'Romantic dinner at Dzul', 'per_person', 2000, 2, 10, $dinner, [$donation, $transport]],
            ['cena-romantica-exclusiva-2', 'Cena romántica exclusiva', 'Exclusive romantic dinner', 'per_person', 6700, 2, 10, $dinner, [$donation, $transport, $proposal]],
            ['pedida-de-mano-1', 'Pedida de mano (opción 1)', 'Proposal (option 1)', 'package', 16000, 2, 2, $afternoon, [$donation]],
            ['pedida-de-mano-2', 'Pedida de mano (opción 2)', 'Proposal (option 2)', 'package', 24000, 2, 2, $afternoon, [$donation]],
            ['pedida-de-mano-3', 'Pedida de mano (opción 3)', 'Proposal (option 3)', 'package', 27400, 2, 2, $afternoon, [$donation]],
            ['pedida-de-mano-sesion-cena-y-ceremonia-sencilla', 'Pedida: sesión, cena y ceremonia sencilla', 'Proposal: session, dinner and simple ceremony', 'package', 33400, 2, 2, $afternoon, [$donation]],
            ['pedida-de-mano-sesion-cena-ceremonia-y-danza-maya', 'Pedida: sesión, cena, ceremonia y danza maya', 'Proposal: session, dinner, ceremony and Mayan dance', 'package', 37400, 2, 2, $afternoon, [$donation]],
        ];
        foreach ($items as $i => [$slug, $es, $en, $model, $price, $min, $max, $template, $extraIds]) {
            $id = DB::insert('experiences', [
                'slug' => $slug, 'kind' => 'slot', 'status' => 'published', 'pricing_model' => $model,
                'base_price' => from_cents($price * 100), 'currency' => 'MXN', 'min_pax' => $min, 'max_pax' => $max,
                'default_capacity' => 20, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            self::translations($id, $es, $en);
            DB::insert('experience_schedules', ['experience_id' => $id, 'template_id' => $template, 'weekdays' => '1,2,3,4,5,6,7']);
            foreach ($extraIds as $n => $gid) {
                DB::insert('experience_extra_groups', ['experience_id' => $id, 'group_id' => $gid, 'sort_order' => $n]);
            }
        }

        // Hospedaje por noche (cabañas: cantidad y horarios por confirmar con el cliente).
        $cabin = DB::insert('experiences', [
            'slug' => 'noche-maya-en-xibalba', 'kind' => 'lodging', 'status' => 'published', 'pricing_model' => 'per_night',
            'base_price' => from_cents(350000), 'currency' => 'MXN', 'min_pax' => 1, 'max_pax' => 4, 'default_capacity' => 1,
            'min_nights' => 1, 'checkin_time' => '15:00', 'checkout_time' => '11:00', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now,
        ]);
        self::translations($cabin, 'Cabaña Maya en Xibalbá', 'Mayan Cabin at Xibalbá');
        DB::insert('experience_extra_groups', ['experience_id' => $cabin, 'group_id' => $donation, 'sort_order' => 0]);

        // Evento 14 de febrero 2027: borrador hasta definir precio y variantes.
        $ev = DB::insert('experiences', [
            'slug' => 'evento-14-febrero-2027', 'kind' => 'event', 'status' => 'draft', 'pricing_model' => 'per_person',
            'base_price' => '0.00', 'currency' => 'MXN', 'min_pax' => 1, 'max_pax' => 10, 'default_capacity' => 20,
            'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now,
        ]);
        self::translations($ev, 'Evento 14 de febrero 2027', 'February 14, 2027 event');
        DB::insert('event_dates', ['experience_id' => $ev, 'event_date' => '2027-02-14', 'time' => '19:00', 'capacity' => 20]);
    }

    private static function translations(int $id, string $es, string $en): void
    {
        foreach (['es' => $es, 'en' => $en] as $lang => $title) {
            DB::insert('experience_translations', ['experience_id' => $id, 'lang' => $lang, 'title' => $title]);
        }
    }
}
