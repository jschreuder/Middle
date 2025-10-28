<?php

use jschreuder\Middle\ApplicationStack;
use jschreuder\Middle\Exception\ApplicationStackException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

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

    expect(fn() => $stack->process($request))->toThrow(ApplicationStackException::class);
});

test('it can clone with logger', function () {
    $middleware = Mockery::mock(MiddlewareInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $stack = new ApplicationStack($middleware);
    $clone = $stack->withLogger($logger);

    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($stack)->not->toBe($clone);
    expect($clone->process($request))->toBe($response);
});

test('it logs middleware execution when logger is provided', function () {
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $stack = (new ApplicationStack($middleware1, $middleware2))->withLogger($logger);

    // Logger should log middleware2 (outer) and middleware1 (inner)
    $logger->shouldReceive('debug')
        ->with('Middleware started: ' . get_class($middleware1))
        ->once();

    $logger->shouldReceive('debug')
        ->with('Middleware finished: ' . get_class($middleware1))
        ->once();

    // middleware2 is the outer middleware, it calls the handler
    $middleware2->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturnUsing(function ($req, $handler) {
            return $handler->handle($req);
        });

    // middleware1 is the inner middleware, it returns the response
    $middleware1->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($stack->process($request))->toBe($response);
});

test('it logs multiple middlewares in correct order', function () {
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $middleware3 = Mockery::mock(MiddlewareInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    $stack = (new ApplicationStack($middleware1, $middleware2, $middleware3))->withLogger($logger);

    // Middlewares are added in LIFO order (last added runs first)
    // middleware3 is called from ApplicationStack->process (not logged)
    // middleware2 is called from the first RequestHandler (logged)
    // middleware1 is called from the second RequestHandler (logged)
    $logger->shouldReceive('debug')
        ->with('Middleware started: ' . get_class($middleware2))
        ->once()
        ->ordered();

    $logger->shouldReceive('debug')
        ->with('Middleware started: ' . get_class($middleware1))
        ->once()
        ->ordered();

    $logger->shouldReceive('debug')
        ->with('Middleware finished: ' . get_class($middleware1))
        ->once()
        ->ordered();

    $logger->shouldReceive('debug')
        ->with('Middleware finished: ' . get_class($middleware2))
        ->once()
        ->ordered();

    // middleware3 calls the handler which will process middleware2
    $middleware3->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturnUsing(function ($req, $handler) {
            return $handler->handle($req);
        });

    // middleware2 calls the handler which will process middleware1
    $middleware2->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturnUsing(function ($req, $handler) {
            return $handler->handle($req);
        });

    // middleware1 returns the response directly
    $middleware1->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($stack->process($request))->toBe($response);
});