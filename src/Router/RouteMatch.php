<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

final readonly class RouteMatch implements RouteMatchInterface
{
    public function __construct(
        private readonly ControllerInterface $controller,
        private readonly array $attributes = []
    ) {}

    #[\Override]
    public function isMatch(): bool
    {
        return true;
    }

    #[\Override]
    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
