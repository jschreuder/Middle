<?php

use jschreuder\Middle\ServerMiddleware\SessionMiddleware;
use jschreuder\Middle\Session\SessionProcessorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

beforeEach(function () {
    $this->processor = Mockery::mock(SessionProcessorInterface::class);
    $this->middleware = new SessionMiddleware($this->processor);
});

test("it can be initialized", function () {
    expect($this->middleware)->toBeInstanceOf(SessionMiddleware::class);
});

test("it can process a request and response", function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $response1 = Mockery::mock(ResponseInterface::class);
    $response2 = Mockery::mock(ResponseInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    $this->processor
        ->shouldReceive("processRequest")
        ->with($request1)
        ->andReturn($request2);

    $requestHandler
        ->shouldReceive("handle")
        ->with($request2)
        ->andReturn($response1);

    $this->processor
        ->shouldReceive("processResponse")
        ->with($request2, $response1)
        ->andReturn($response2);

    expect($this->middleware->process($request1, $requestHandler))->toBe(
        $response2,
    );
});
