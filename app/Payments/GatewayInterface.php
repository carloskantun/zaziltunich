<?php
declare(strict_types=1);

namespace App\Payments;

/**
 * Contrato de pasarelas de pago. Cada adaptador (Stripe, PayPal, MercadoPago) implementa esto.
 * ManualGateway es la única operativa hoy; las demás requieren credenciales para probarse.
 */
interface GatewayInterface
{
    public function code(): string;

    public function label(): string;

    /** ¿Está configurada y activa? */
    public function enabled(): bool;

    /**
     * Inicia el cobro de $amountCents para la reserva. Devuelve URL a la que redirigir al cliente.
     */
    public function start(array $booking, int $amountCents): string;
}
