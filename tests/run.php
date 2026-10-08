<?php
declare(strict_types=1);

// Pruebas sin dependencias: php tests/run.php
require __DIR__ . '/../app/bootstrap.php';

use App\Core\Config;
use App\Core\Database as DB;
use App\Domain\{Availability, BookingService, Catalog, ContentLayout, Pricing, Seeder, Settings};

$pass = 0;
$fail = 0;
function check(string $name, mixed $got, mixed $want): void
{
    global $pass, $fail;
    if ($got === $want) {
        $pass++;
        return;
    }
    $fail++;
    echo "FALLA: $name\n  esperado: " . json_encode($want) . "\n  obtenido: " . json_encode($got) . "\n";
}

Config::set(['base_url' => 'http://test', 'timezone' => 'America/Merida']);
// Por defecto SQLite en memoria. Con ZT_MYSQL_DB (+ ZT_MYSQL_USER, ZT_MYSQL_PASS) se prueba contra MySQL/MariaDB (borra las tablas de esa base).
if (getenv('ZT_MYSQL_DB')) {
    DB::connect(['driver' => 'mysql', 'host' => getenv('ZT_MYSQL_HOST') ?: 'localhost', 'name' => getenv('ZT_MYSQL_DB'), 'user' => getenv('ZT_MYSQL_USER') ?: 'root', 'pass' => getenv('ZT_MYSQL_PASS') ?: '']);
    DB::exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (DB::all('SHOW TABLES') as $row) {
        DB::exec('DROP TABLE `' . array_values($row)[0] . '`');
    }
    DB::exec('SET FOREIGN_KEY_CHECKS = 1');
} else {
    DB::connect(['driver' => 'sqlite', 'path' => ':memory:']);
}
DB::runSchema((string) file_get_contents(ROOT . '/database/schema.sql'));
foreach (['002_content', '003_categories'] as $f) {
    DB::runSchema((string) file_get_contents(ROOT . '/database/' . $f . '.sql'));
}
Seeder::settings();
Seeder::catalog();

$exp = fn (string $slug) => Catalog::bySlug($slug, false);
$future = date('Y-m-d', strtotime('+20 days'));
$cenote = $exp('cenote-museo');

// --- Precios ---
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 3, 'extras' => [1 => 0]]);
check('donación requerida sin elegir', $q['ok'], false);
$donationGroup = $cenote['extra_groups'][0];
$d100 = $donationGroup['options'][2]['id'];
$transportId = $cenote['extra_groups'][1]['id'];
$mealGroup = $cenote['extra_groups'][2];
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 3, 'extras' => [$donationGroup['id'] => $d100]]);
check('3 pax $400 + donación $100', $q['total'], 3 * 40000 + 10000);
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 3, 'extras' => [$donationGroup['id'] => $d100, $transportId => '1']]);
check('transporte auto 3 pax $800', $q['total'], 3 * 40000 + 10000 + 80000);
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 6, 'extras' => [$donationGroup['id'] => $d100, $transportId => '1']]);
check('transporte auto 6 pax $1200', $q['extras_total'], 10000 + 120000);
$mealOpt = $mealGroup['options'][0]['id'];
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 2, 'extras' => [$donationGroup['id'] => $d100, $mealGroup['id'] => [$mealOpt => 2]]]);
check('comida ×2 = $700', $q['extras_total'], 10000 + 70000);
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 11, 'extras' => [$donationGroup['id'] => $d100]]);
check('pax fuera de rango', $q['ok'], false);

$ped = $exp('pedida-de-mano-1');
$pd = $ped['extra_groups'][0];
$q = Pricing::quote($ped, ['date' => $future, 'time' => '15:00', 'pax' => 2, 'extras' => [$pd['id'] => $pd['options'][0]['id']]]);
check('paquete fijo $16,000', $q['total'], 1600000);

// anticipo
DB::update('experiences', ['payment_mode' => 'deposit', 'deposit_percent' => '30'], 'id = ?', [$cenote['id']]);
$cenote = $exp('cenote-museo');
$q = Pricing::quote($cenote, ['date' => $future, 'time' => '12:00', 'pax' => 3, 'extras' => [$donationGroup['id'] => $d100]]);
check('anticipo 30%', $q['deposit'], intdiv(130000 * 30, 100));

// sobreescritura de precio
DB::insert('price_overrides', ['experience_id' => $cenote['id'], 'label' => 'Temporada alta', 'date_from' => $future, 'date_to' => $future, 'time' => '13:00', 'weekdays' => null, 'price' => '500.00']);
$cenote = $exp('cenote-museo');
check('override por hora', Pricing::unitPrice($cenote, $future, '13:00', 2), 50000);
check('sin override a otra hora', Pricing::unitPrice($cenote, $future, '12:00', 2), 40000);

// escalonado
$tiered = DB::insert('experiences', ['slug' => 'tiered', 'kind' => 'slot', 'status' => 'published', 'pricing_model' => 'tiered', 'base_price' => '900.00', 'min_pax' => 1, 'max_pax' => 10, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
DB::insert('price_tiers', ['experience_id' => $tiered, 'min_pax' => 1, 'max_pax' => 3, 'price' => '1000.00']);
DB::insert('price_tiers', ['experience_id' => $tiered, 'min_pax' => 4, 'max_pax' => null, 'price' => '800.00']);
$t = Catalog::load($tiered);
check('escalonado 2 pax', Pricing::quote($t, ['date' => $future, 'pax' => 2])['total'], 200000);
check('escalonado 5 pax', Pricing::quote($t, ['date' => $future, 'pax' => 5])['total'], 400000);

// hospedaje
$cabin = $exp('noche-maya-en-xibalba');
$cabinDonation = $cabin['extra_groups'][0];
$cin = date('Y-m-d', strtotime('+30 days'));
$cout = date('Y-m-d', strtotime('+33 days'));
$q = Pricing::quote($cabin, ['date' => $cin, 'end_date' => $cout, 'pax' => 2, 'extras' => [$cabinDonation['id'] => $cabinDonation['options'][0]['id']]]);
check('3 noches × $3,500', $q['total'], 3 * 350000);
check('salida antes de entrada', Pricing::quote($cabin, ['date' => $cout, 'end_date' => $cin, 'pax' => 2])['ok'], false);

// --- Disponibilidad y reservas ---
$slots = Availability::slots($cenote, $future);
check('horarios mañana', array_column($slots, 'time'), ['12:00', '13:00']);
check('cupo inicial', $slots[0]['left'], 20);

$sel = ['date' => $future, 'time' => '12:00', 'pax' => 18, 'extras' => [$donationGroup['id'] => $d100]];
$cenote2 = $cenote;
$cenote2['max_pax'] = 20;
$r = BookingService::create($cenote2, $sel, ['name' => 'Ana', 'email' => 'ana@example.com', 'phone' => '999'], 'web', 'es');
check('reserva 18 pax ok', $r['ok'], true);
check('estado inicial', $r['booking']['status'], 'pending');
check('total guardado', $r['booking']['total'], 18 * 40000 + 10000);
$left = Availability::slots($cenote, $future)[0]['left'];
check('cupo tras reserva', $left, 2);
$r2 = BookingService::create($cenote2, ['pax' => 3] + $sel, ['name' => 'Beto', 'email' => 'beto@example.com'], 'web', 'es');
check('sobreventa rechazada', $r2['ok'], false);
check('código de error', $r2['errors'][0]['code'] ?? '', 'err.slot_full');

BookingService::addPayment($r['booking']['id'], 100000, 'manual', 'transferencia', 'ref1');
$b = BookingService::find($r['booking']['id']);
check('pago parcial -> anticipo', $b['status'], 'deposit_paid');
BookingService::addPayment($b['id'], $b['total'] - 100000, 'manual');
check('pago completo -> pagada', BookingService::find($b['id'])['status'], 'paid');
BookingService::setStatus($b['id'], 'cancelled');
check('cancelar libera cupo', Availability::slots($cenote, $future)[0]['left'], 20);

// retención vencida libera cupo
$r3 = BookingService::create($cenote2, ['pax' => 5] + $sel, ['name' => 'Carla', 'email' => 'carla@example.com'], 'web', 'es');
DB::update('bookings', ['hold_expires_at' => '2000-01-01 00:00:00'], 'id = ?', [$r3['booking']['id']]);
check('retención vencida no cuenta', Availability::slots($cenote, $future)[0]['left'], 20);
check('expirar retenciones', BookingService::expireHolds(), 1);

// bloqueo
DB::insert('blocks', ['experience_id' => $cenote['id'], 'date_from' => $future, 'date_to' => $future, 'time' => '13:00', 'reason' => 'Mantenimiento']);
check('bloqueo por hora', array_column(Availability::slots($cenote, $future), 'time'), ['12:00']);

// capacidad compartida
$gid = DB::insert('capacity_groups', ['name' => 'Cenote Huinik', 'capacity' => 4]);
$a = $exp('comida-en-cenote-huinik');
$b2 = $exp('cena-romantica-huinik');
DB::update('experiences', ['capacity_group_id' => $gid], 'id IN (?, ?)', [$a['id'], $b2['id']]);
$a = $exp('comida-en-cenote-huinik');
$b2 = $exp('cena-romantica-huinik');
$dg = $a['extra_groups'][0];
$sa = ['date' => $future, 'time' => '17:00', 'pax' => 3, 'extras' => [$dg['id'] => $dg['options'][0]['id']]];
check('grupo compartido reserva A', BookingService::create($a, $sa, ['name' => 'X', 'email' => 'x@example.com'])['ok'], true);
$sb = ['pax' => 2] + $sa;
$sb['extras'] = [$b2['extra_groups'][0]['id'] => $b2['extra_groups'][0]['options'][0]['id']];
check('grupo compartido bloquea B', BookingService::create($b2, $sb, ['name' => 'Y', 'email' => 'y@example.com'])['ok'], false);

// hospedaje: dos reservas solapadas con 1 cabaña
$sc = ['date' => $cin, 'end_date' => $cout, 'pax' => 2, 'extras' => [$cabinDonation['id'] => $cabinDonation['options'][0]['id']]];
check('cabaña libre', BookingService::create($cabin, $sc, ['name' => 'Z', 'email' => 'z@example.com'])['ok'], true);
check('cabaña ocupada', BookingService::create($cabin, $sc, ['name' => 'W', 'email' => 'w@example.com'])['ok'], false);
$next = ['date' => $cout, 'end_date' => date('Y-m-d', strtotime($cout . ' +1 day'))] + $sc;
check('salida y entrada el mismo día', BookingService::create($cabin, $next, ['name' => 'V', 'email' => 'v@example.com'])['ok'], true);

// pasado
check('fecha pasada no reservable', Availability::slots($cenote, date('Y-m-d', strtotime('-1 day'))), []);

// dinero
check('money entero', money(123400), '$ 1,234');
check('money centavos', money(123450), '$ 1,234.50');
check('to_cents', to_cents('1,234.50'), 123450);

// Las columnas importadas conservan contenido, enlaces, imágenes y controles.
$source = '<section class="sec"><div class="slider"><img src="/uploads/foto.webp" alt="Cenote"><button type="button">Siguiente</button></div><p>Experiencia única: <a href="/romance">México &amp; Yucatán</a></p></section>';
$columns = ContentLayout::columns($source);
check('columnas conservan texto UTF-8', str_contains($columns, 'Experiencia única:'), true);
check('columnas conservan enlace', str_contains($columns, 'href="/romance"'), true);
check('columnas conservan imagen y control', substr_count($columns, '<img') + substr_count($columns, '<button'), 2);
check('columnas no se duplican al repetir', ContentLayout::columns($columns), $columns);
check('galería sin texto no cambia de distribución', ContentLayout::columns('<section class="sec"><div class="slider">Foto</div></section>'), '<section class="sec"><div class="slider">Foto</div></section>');

echo "\n$pass correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
