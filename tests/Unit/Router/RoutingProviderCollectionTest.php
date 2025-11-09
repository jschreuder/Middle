<?php

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderCollection;
use jschreuder\Middle\Router\RoutingProviderInterface;

test("it can be initialized", function () {
    $collection = new RoutingProviderCollection();

    expect($collection)->toBeInstanceOf(RoutingProviderCollection::class);
});

test("it can work with one provider", function () {
    $router = Mockery::mock(RouterInterface::class);
    $routingProvider = Mockery::mock(RoutingProviderInterface::class);

    $collection = new RoutingProviderCollection($routingProvider);

    $routingProvider->shouldReceive("registerRoutes")->with($router)->once();

    $collection->registerRoutes($router);
});

test("it can work with multiple providers", function () {
    $router = Mockery::mock(RouterInterface::class);
    $routingProvider1 = Mockery::mock(RoutingProviderInterface::class);
    $routingProvider2 = Mockery::mock(RoutingProviderInterface::class);
    $routingProvider3 = Mockery::mock(RoutingProviderInterface::class);

    $collection = new RoutingProviderCollection(
        $routingProvider1,
        $routingProvider2,
        $routingProvider3,
    );

    $routingProvider1->shouldReceive("registerRoutes")->with($router)->once();
    $routingProvider2->shouldReceive("registerRoutes")->with($router)->once();
    $routingProvider3->shouldReceive("registerRoutes")->with($router)->once();

    $collection->registerRoutes($router);
});
