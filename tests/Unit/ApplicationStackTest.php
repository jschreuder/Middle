<?php

use jschreuder\Middle\ApplicationStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('it can be initialized', function () {
    $stack = new ApplicationStack(
        Mockery::mock(MiddlewareInterface::class)
    );
    
    expect($stack)->toBeInstanceOf(ApplicationStack::class);
});

test('it can clone with middleware', function () {
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $request = Mockery::mock(ServerRequestInterface::class);
    $response1 = Mockery::mock(ResponseInterface::class);
    $response2 = Mockery::mock(ResponseInterface::class);

    $stack = new ApplicationStack($middleware1);
    $clone = $stack->withMiddleware($middleware2);

    $middleware1->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response1);
    
    $middleware2->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response2);

    expect($stack->process($request))->toBe($response1);
    expect($clone->process($request))->toBe($response2);
});

test('it can clone without middleware', function () {
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $request = Mockery::mock(ServerRequestInterface::class);
    $response1 = Mockery::mock(ResponseInterface::class);
    $response2 = Mockery::mock(ResponseInterface::class);

    $stack = new ApplicationStack($middleware1, $middleware2);
    $clone = $stack->withoutMiddleware($middleware2);

    $middleware1->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response1);
    
    $middleware2->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response2);

    expect($stack->process($request))->toBe($response2);
    expect($clone->process($request))->toBe($response1);
});

test('it throws error on empty stack', function () {
    $stack = new ApplicationStack();
    $request = Mockery::mock(ServerRequestInterface::class);

    expect(fn() => $stack->process($request))->toThrow(RuntimeException::class);
}); 