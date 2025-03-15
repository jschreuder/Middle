<?php

use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerRunner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('it can be initialized', function () {
    $runner = new ControllerRunner();
    expect($runner)->toBeInstanceOf(ControllerRunner::class);
});

test('it can execute a controller', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    
    $controller = CallableController::fromCallable(function () use ($response): ResponseInterface {
        return $response;
    });
    
    $request->shouldReceive('getAttribute')
        ->with('controller')
        ->andReturn($controller);
    
    $runner = new ControllerRunner();
    expect($runner->process($request, $requestHandler))->toBe($response);
});

test('it will error on invalid controller', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    
    $controller = function () {
        return 'an invalid response';
    };
    
    $request->shouldReceive('getAttribute')
        ->with('controller')
        ->andReturn($controller);
    
    $runner = new ControllerRunner();
    expect(fn() => $runner->process($request, $requestHandler))->toThrow(RuntimeException::class);
}); 