<?php

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\NoRouteMatch;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

beforeEach(function () {
    $this->router = Mockery::mock(RouterInterface::class);
    $this->fallbackController = Mockery::mock(ControllerInterface::class);
    $this->middleware = new RoutingMiddleware(
        $this->router,
        $this->fallbackController,
    );
});

test("it can be initialized", function () {
    expect($this->middleware)->toBeInstanceOf(RoutingMiddleware::class);
});

test("it can assign a controller and attributes", function () {
    $name = "test";
    $controller = Mockery::mock(ControllerInterface::class);
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $request3 = Mockery::mock(ServerRequestInterface::class);
    $request4 = Mockery::mock(ServerRequestInterface::class);
    $request5 = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $attributes = ["v1" => 1, "v2" => 2];
    $routeMatch = new RouteMatch($name, $controller, $attributes);

    $this->router
        ->shouldReceive("parseRequest")
        ->with($request1)
        ->andReturn($routeMatch);

    $request1
        ->shouldReceive("withAttribute")
        ->with("route", $name)
        ->andReturn($request2);

    $request2
        ->shouldReceive("withAttribute")
        ->with("controller", $controller)
        ->andReturn($request3);

    $request3
        ->shouldReceive("withAttribute")
        ->with("v1", $attributes["v1"])
        ->andReturn($request4);

    $request4
        ->shouldReceive("withAttribute")
        ->with("v2", $attributes["v2"])
        ->andReturn($request5);

    $requestHandler
        ->shouldReceive("handle")
        ->with($request5)
        ->andReturn($response);

    expect($this->middleware->process($request1, $requestHandler))->toBe(
        $response,
    );
});

test("it can assign fallback controller without a match", function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $routeMatch = new NoRouteMatch();

    $this->router
        ->shouldReceive("parseRequest")
        ->with($request1)
        ->andReturn($routeMatch);

    $request1
        ->shouldReceive("withAttribute")
        ->with("controller", $this->fallbackController)
        ->andReturn($request2);

    $requestHandler
        ->shouldReceive("handle")
        ->with($request2)
        ->andReturn($response);

    expect($this->middleware->process($request1, $requestHandler))->toBe(
        $response,
    );
});
