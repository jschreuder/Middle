<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

class RoutingProviderCollection implements RoutingProviderInterface
{
    /** @var  RoutingProviderInterface[] */
    private $routingProviders;

    public function __construct(RoutingProviderInterface ...$routingProviders)
    {
        $this->routingProviders = $routingProviders;
    }

    public function registerRoutes(RouterInterface $router): void
    {
        foreach ($this->routingProviders as $routingProvider) {
            $routingProvider->registerRoutes($router);
        }
    }
}