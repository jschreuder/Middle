<?php

use jschreuder\Middle\Session\SessionInterface;
use jschreuder\Middle\Session\LaminasSessionProcessor;
use Laminas\Session\Config\ConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

beforeEach(function () {
    $this->laminasSessionConfig = Mockery::mock(ConfigInterface::class);
    $this->processor = new LaminasSessionProcessor($this->laminasSessionConfig);
});

test("it can be initialized", function () {
    expect($this->processor)->toBeInstanceOf(LaminasSessionProcessor::class);
});

test("it can process a request", function () {
    $uri = Mockery::mock(UriInterface::class);
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);

    $uri->shouldReceive("getHost")->andReturn("localhost");

    $request1->shouldReceive("getUri")->andReturn($uri);

    $request1
        ->shouldReceive("withAttribute")
        ->with("session", Mockery::type(SessionInterface::class))
        ->andReturn($request2);

    expect($this->processor->processRequest($request1))->toBe($request2);
});

test("it can process a response", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response = Mockery::mock(ResponseInterface::class);

    expect($this->processor->processResponse($request, $response))->toBe(
        $response,
    );
});
