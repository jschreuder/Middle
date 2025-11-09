<?php

use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

beforeEach(function () {
    $this->errorHandler = function () {};
});

test("it can be initialized", function () {
    $middleware = new RequestValidatorMiddleware($this->errorHandler);
    expect($middleware)->toBeInstanceOf(RequestValidatorMiddleware::class);
});

test("it can successfully validate a request", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $validator = Mockery::mock(RequestValidatorInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $request
        ->shouldReceive("getAttribute")
        ->with("controller")
        ->andReturn($validator);

    $validator->shouldReceive("validateRequest")->with($request)->once();

    $requestHandler
        ->shouldReceive("handle")
        ->with($request)
        ->andReturn($response);

    $middleware = new RequestValidatorMiddleware($this->errorHandler);
    expect($middleware->process($request, $requestHandler))->toBe($response);
});

test("it can fail to validate a request", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $validator = Mockery::mock(RequestValidatorInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $request
        ->shouldReceive("getAttribute")
        ->with("controller")
        ->andReturn($validator);

    $validator
        ->shouldReceive("validateRequest")
        ->with($request)
        ->andThrow(new ValidationFailedException([]));

    $requestHandler->shouldNotReceive("handle");

    $errorHandler = fn() => $response;

    $middleware = new RequestValidatorMiddleware($errorHandler);
    expect($middleware->process($request, $requestHandler))->toBe($response);
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

    $middleware = new RequestValidatorMiddleware($this->errorHandler);
    expect($middleware->process($request, $requestHandler))->toBe($response);
});
