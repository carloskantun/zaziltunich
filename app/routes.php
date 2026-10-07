<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\{ApiController, PublicController};
use App\Core\Router;

return static function (Router $r): void {
    $r->get('/', [PublicController::class, 'home']);
    $r->get('/reservaciones', [PublicController::class, 'list']);
    $r->get('/reservaciones/{slug}', [PublicController::class, 'experience']);
    $r->post('/checkout', [PublicController::class, 'checkout']);
    $r->post('/checkout/confirmar', [PublicController::class, 'confirm']);
    $r->get('/pago/{code}', [PublicController::class, 'pay']);
    $r->get('/gracias/{code}', [PublicController::class, 'thanks']);
    $r->get('/voucher/{code}', [PublicController::class, 'voucher']);

    $r->get('/api/month', [ApiController::class, 'month']);
    $r->get('/api/slots', [ApiController::class, 'slots']);
    $r->post('/api/quote', [ApiController::class, 'quote']);

    $r->get('/admin', [Admin\DashboardController::class, 'index']);
    $r->get('/admin/login', [Admin\AuthController::class, 'form']);
    $r->post('/admin/login', [Admin\AuthController::class, 'login']);
    $r->post('/admin/logout', [Admin\AuthController::class, 'logout']);

    $r->get('/admin/reservas', [Admin\BookingsController::class, 'index']);
    $r->get('/admin/reservas/nueva', [Admin\BookingsController::class, 'create']);
    $r->post('/admin/reservas/nueva', [Admin\BookingsController::class, 'store']);
    $r->get('/admin/reservas/{id}', [Admin\BookingsController::class, 'show']);
    $r->post('/admin/reservas/{id}/estado', [Admin\BookingsController::class, 'status']);
    $r->post('/admin/reservas/{id}/pago', [Admin\BookingsController::class, 'payment']);
    $r->post('/admin/reservas/{id}/notas', [Admin\BookingsController::class, 'notes']);

    $r->get('/admin/calendario', [Admin\CalendarController::class, 'index']);

    $r->get('/admin/experiencias', [Admin\ExperiencesController::class, 'index']);
    $r->get('/admin/experiencias/nueva', [Admin\ExperiencesController::class, 'create']);
    $r->post('/admin/experiencias/guardar', [Admin\ExperiencesController::class, 'save']);
    $r->get('/admin/experiencias/{id}', [Admin\ExperiencesController::class, 'edit']);
    $r->post('/admin/experiencias/{id}/duplicar', [Admin\ExperiencesController::class, 'duplicate']);
    $r->post('/admin/experiencias/{id}/eliminar', [Admin\ExperiencesController::class, 'delete']);

    $r->get('/admin/horarios', [Admin\ResourcesController::class, 'templates']);
    $r->post('/admin/horarios', [Admin\ResourcesController::class, 'saveTemplates']);
    $r->get('/admin/extras', [Admin\ResourcesController::class, 'extras']);
    $r->post('/admin/extras', [Admin\ResourcesController::class, 'saveExtras']);
    $r->get('/admin/bloqueos', [Admin\ResourcesController::class, 'blocks']);
    $r->post('/admin/bloqueos', [Admin\ResourcesController::class, 'saveBlocks']);

    $r->get('/admin/clientes', [Admin\CustomersController::class, 'index']);
    $r->get('/admin/ajustes', [Admin\SettingsController::class, 'index']);
    $r->post('/admin/ajustes', [Admin\SettingsController::class, 'save']);
    $r->post('/admin/usuarios', [Admin\SettingsController::class, 'saveUser']);
};
