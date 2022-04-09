<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

final class RouteMatch implements RouteMatchInterface
{
    private ControllerInterface $controller;
    private array $attributes;

    public function __construct(ControllerInterface $controller, array $attributes = [])
    {
        $this->controller = $controller;
        $this->attributes = $attributes;
    }

    public function isMatch(): bool
    {
        return true;
    }

    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
