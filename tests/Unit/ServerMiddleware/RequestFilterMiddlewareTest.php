<?php

use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

test("it can be initialized", function () {
    $middleware = new RequestFilterMiddleware();
    expect($middleware)->toBeInstanceOf(RequestFilterMiddleware::class);
});

test("it can filter a request", function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $filter = Mockery::mock(RequestFilterInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $request1
        ->shouldReceive("getAttribute")
        ->with("controller")
        ->andReturn($filter);

    $filter
        ->shouldReceive("filterRequest")
        ->with($request1)
        ->andReturn($request2);

    $requestHandler
        ->shouldReceive("handle")
        ->with($request2)
        ->andReturn($response);

    $middleware = new RequestFilterMiddleware();
    expect($middleware->process($request1, $requestHandler))->toBe($response);
});

test("it can do nothing", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $request
        ->shouldReceive("getAttribute")
        ->with("controller")
        ->andReturn("trim");

    $requestHandler
        ->shouldReceive("handle")
        ->with($request)
        ->andReturn($response);

    $middleware = new RequestFilterMiddleware();
    expect($middleware->process($request, $requestHandler))->toBe($response);
});
