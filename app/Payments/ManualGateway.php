<?php
declare(strict_types=1);

namespace App\Payments;

/** Pago manual: la reserva queda registrada y el equipo registra el pago desde el panel. */
final class ManualGateway implements GatewayInterface
{
    public function code(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Pago con el equipo / transferencia';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function start(array $booking, int $amountCents): string
    {
        return raw_url('pago/' . $booking['code']);
    }
}
