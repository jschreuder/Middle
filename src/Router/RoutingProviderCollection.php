<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

final readonly class RoutingProviderCollection implements RoutingProviderInterface
{
    /** @var  RoutingProviderInterface[] */
    private readonly array $routingProviders;

    public function __construct(RoutingProviderInterface ...$routingProviders)
    {
        $this->routingProviders = $routingProviders;
    }

    #[\Override]
    public function registerRoutes(RouterInterface $router): void
    {
        foreach ($this->routingProviders as $routingProvider) {
            $routingProvider->registerRoutes($router);
        }
    }
}
