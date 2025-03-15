<?php

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->errorController = Mockery::mock(ControllerInterface::class);
    $this->middleware = new ErrorHandlerMiddleware($this->logger, $this->errorController);
});

test('it can be initialized', function () {
    expect($this->middleware)->toBeInstanceOf(ErrorHandlerMiddleware::class);
});

test('it will do nothing without an exception', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    
    $requestHandler->shouldReceive('handle')
        ->with($request)
        ->andReturn($response);
    
    $this->logger->shouldNotReceive('alert');
    
    expect($this->middleware->process($request, $requestHandler))->toBe($response);
});

test('it will log and process exception when thrown', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    
    $msg = uniqid();
    $code = 418;
    $exception = new RuntimeException($msg, $code);
    
    $requestHandler->shouldReceive('handle')
        ->with($request)
        ->andThrow($exception);
    
    $this->logger->shouldReceive('alert')
        ->with($msg, Mockery::on(function ($context) {
            return isset($context['file']) && $context['file'] === __FILE__;
        }));
    
    $request->shouldReceive('withAttribute')
        ->with('error', $exception)
        ->andReturn($request2);
    
    $this->errorController->shouldReceive('execute')
        ->with($request2)
        ->andReturn($response);
    
    expect($this->middleware->process($request, $requestHandler))->toBe($response);
}); 