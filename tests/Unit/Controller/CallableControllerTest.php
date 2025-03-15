<?php

use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

beforeEach(function () {
    $this->response = Mockery::mock(ResponseInterface::class);
    $this->controller = CallableController::fromCallable(function () {
        return $this->response;
    });
});

test('it can be initialized', function () {
    expect($this->controller)
        ->toBeInstanceOf(CallableController::class)
        ->toBeInstanceOf(ControllerInterface::class);
});

test('it can execute', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    
    expect($this->controller->execute($request))->toBe($this->response);
});

test('it errors on non response', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    
    $controller = CallableController::fromCallable(function () {
        return 'nope';
    });
    
    expect(fn() => $controller->execute($request))->toThrow(TypeError::class);
});

test('it can create a factory', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = $this->response;
    
    $controllerFactory = CallableController::factoryFromCallable(function () use ($response) {
        return $response;
    });
    $controller = $controllerFactory();
    
    expect($controller->execute($request))->toBe($response);
}); 