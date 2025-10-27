<?php

use jschreuder\Middle\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

test('it can call process', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware = Mockery::mock(MiddlewareInterface::class);
    
    $stack = new \SplStack();
    $handler = new RequestHandler($stack);
    $stack->push($middleware);
    
    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);
    
    expect($handler->handle($request))->toBe($response);
});

test('it can call process with multiple middlewares', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $middleware3 = Mockery::mock(MiddlewareInterface::class);
    
    $stack = new \SplStack();
    $stack->push($middleware1);
    $stack->push($middleware2);
    $stack->push($middleware3);
    
    $handler = new RequestHandler($stack);
    
    $middleware3->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);
    
    expect($handler->handle($request))->toBe($response);
});

test('it cannot call process twice', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware = Mockery::mock(MiddlewareInterface::class);
    
    $stack = new \SplStack();
    $stack->push($middleware);
    $stack->push($middleware);
    
    $handler = new RequestHandler($stack);
    
    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);
    
    expect($handler->handle($request))->toBe($response);
    expect(fn() => $handler->handle($request))->toThrow(RuntimeException::class);
});

test('it will error when called on empty stack', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware = Mockery::mock(MiddlewareInterface::class);

    $stack = new \SplStack();
    $handler = new RequestHandler($stack);
    $stack->push($middleware);

    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($handler->handle($request))->toBe($response);
    expect(fn() => $handler->handle($request))->toThrow(RuntimeException::class);
});

test('it logs middleware start and finish when logger is provided', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware = Mockery::mock(MiddlewareInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $stack = new \SplStack();
    $stack->push($middleware);

    $handler = new RequestHandler($stack, $logger);

    $logger->shouldReceive('debug')
        ->with('Middleware started: ' . get_class($middleware))
        ->once();

    $logger->shouldReceive('debug')
        ->with('Middleware finished: ' . get_class($middleware))
        ->once();

    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($handler->handle($request))->toBe($response);
});

test('it logs multiple middlewares in execution order', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware1 = Mockery::mock(MiddlewareInterface::class);
    $middleware2 = Mockery::mock(MiddlewareInterface::class);
    $logger = Mockery::mock(LoggerInterface::class);

    $stack = new \SplStack();
    $stack->push($middleware1);
    $stack->push($middleware2);

    $handler = new RequestHandler($stack, $logger);

    // middleware2 executes first (LIFO), then middleware1
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

    $middleware2->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturnUsing(function ($req, $handler) use ($response) {
            return $handler->handle($req);
        });

    $middleware1->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($handler->handle($request))->toBe($response);
});

test('it does not log when logger is not provided', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $middleware = Mockery::mock(MiddlewareInterface::class);

    $stack = new \SplStack();
    $stack->push($middleware);

    // No logger provided - should work without errors
    $handler = new RequestHandler($stack);

    $middleware->shouldReceive('process')
        ->with($request, Mockery::type(RequestHandlerInterface::class))
        ->andReturn($response);

    expect($handler->handle($request))->toBe($response);
});