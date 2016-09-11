<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

interface RouteMatchInterface
{
    /** Whether it contains an actual matched route */
    public function isMatch() : bool;

    /** Callable controller */
    public function getController() : callable;

    /** Array of attributes determined by routing */
    public function getAttributes() : array;
}