<?php
declare(strict_types=1);

namespace App\Payments;

final class Gateways
{
    /** @return GatewayInterface[] */
    public static function enabled(): array
    {
        return array_values(array_filter([new ManualGateway()], static fn (GatewayInterface $g) => $g->enabled()));
    }

    public static function default(): GatewayInterface
    {
        return self::enabled()[0];
    }
}
