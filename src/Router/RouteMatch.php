<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

final class RouteMatch implements RouteMatchInterface
{
    public function __construct(
        private ControllerInterface $controller,
        private array $attributes = []
    )
    {
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
