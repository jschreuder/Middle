<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderCollection;
use jschreuder\Middle\Router\RoutingProviderInterface;
use PhpSpec\ObjectBehavior;

class RoutingProviderCollectionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RoutingProviderCollection::class);
    }

    public function it_can_work_with_one_provider(RouterInterface $router, RoutingProviderInterface $routingProvider)
    {
        $this->beConstructedWith($routingProvider);
        $routingProvider->registerRoutes($router)->shouldBeCalled();
        $this->registerRoutes($router);
    }

    public function it_can_work_with_multiple_providers(
        RouterInterface $router,
        RoutingProviderInterface $routingProvider1,
        RoutingProviderInterface $routingProvider2,
        RoutingProviderInterface $routingProvider3
    )
    {
        $this->beConstructedWith($routingProvider1, $routingProvider2, $routingProvider3);
        $routingProvider1->registerRoutes($router)->shouldBeCalled();
        $routingProvider2->registerRoutes($router)->shouldBeCalled();
        $routingProvider3->registerRoutes($router)->shouldBeCalled();
        $this->registerRoutes($router);
    }
}
