<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

interface RoutingProviderInterface
{
    public function registerRoutes(RouterInterface $router);
}
