<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

final class NoRouteMatch implements RouteMatchInterface
{
    public function isMatch(): bool
    {
        return false;
    }
    
    public function getController(): ControllerInterface
    {
        throw new \RuntimeException('An unmatched route does not have a controller');
    }

    public function getAttributes(): array
    {
        throw new \RuntimeException('An unmatched route does not have attributes');
    }
}
