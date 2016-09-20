<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;

interface RouteMatchInterface
{
    /** Whether it contains an actual matched route */
    public function isMatch() : bool;

    /** Callable controller */
    public function getController() : ControllerInterface;

    /** Array of attributes determined by routing */
    public function getAttributes() : array;
}