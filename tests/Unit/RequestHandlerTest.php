<?php

use jschreuder\Middle\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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