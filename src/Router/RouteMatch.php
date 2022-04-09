<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

final class RouteMatch implements RouteMatchInterface
{
    private ?ControllerInterface $controller;
    private array $attributes;

    public function __construct(bool $matched, ControllerInterface $controller = null, array $attributes = [])
    {
        if ($matched && (is_null($controller))) {
            throw new \RuntimeException('A matched route must always be provided a controller');
        }
        $this->controller = $controller;
        $this->attributes = $attributes;
    }

    public function isMatch(): bool
    {
        return !is_null($this->controller);
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
