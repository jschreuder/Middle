<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

interface RouteMatchInterface
{
    /** Name of the matched route */
    public function getName(): string;

    /** Whether it contains an actual matched route */
    public function isMatch(): bool;

    /** Callable controller */
    public function getController(): ControllerInterface;

    /** Array of attributes determined by routing */
    public function getAttributes(): array;
}
