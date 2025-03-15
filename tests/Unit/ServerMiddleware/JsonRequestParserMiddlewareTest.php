<?php

use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('it can be initialized', function () {
    $middleware = new JsonRequestParserMiddleware();
    expect($middleware)->toBeInstanceOf(JsonRequestParserMiddleware::class);
});

test('it can parse json', function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $body = Mockery::mock(StreamInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    
    $array = ['test' => 42, 'next' => 'best'];
    
    $body->shouldReceive('getContents')
        ->andReturn(json_encode($array));
    
    $request1->shouldReceive('getBody')
        ->andReturn($body);
    $request1->shouldReceive('getHeaderLine')
        ->with('Content-Type')
        ->andReturn('application/json');
    $request1->shouldReceive('withParsedBody')
        ->with($array)
        ->andReturn($request2);
    
    $requestHandler->shouldReceive('handle')
        ->with($request2)
        ->andReturn($response);
    
    $middleware = new JsonRequestParserMiddleware();
    expect($middleware->process($request1, $requestHandler))->toBe($response);
});

test('it can parse json with charset', function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $body = Mockery::mock(StreamInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    
    $array = ['test' => 42, 'next' => 'best'];
    
    $body->shouldReceive('getContents')
        ->andReturn(json_encode($array));
    
    $request1->shouldReceive('getBody')
        ->andReturn($body);
    $request1->shouldReceive('getHeaderLine')
        ->with('Content-Type')
        ->andReturn('application/json;charset=utf-8');
    $request1->shouldReceive('withParsedBody')
        ->with($array)
        ->andReturn($request2);
    
    $requestHandler->shouldReceive('handle')
        ->with($request2)
        ->andReturn($response);
    
    $middleware = new JsonRequestParserMiddleware();
    expect($middleware->process($request1, $requestHandler))->toBe($response);
});

test('it can parse non default json content types', function () {
    $request1 = Mockery::mock(ServerRequestInterface::class);
    $request2 = Mockery::mock(ServerRequestInterface::class);
    $body = Mockery::mock(StreamInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    
    $array = ['test' => 42, 'next' => 'best'];
    
    $body->shouldReceive('getContents')
        ->andReturn(json_encode($array));
    
    $request1->shouldReceive('getBody')
        ->andReturn($body);
    $request1->shouldReceive('getHeaderLine')
        ->with('Content-Type')
        ->andReturn('application/vnd.api+json');
    $request1->shouldReceive('withParsedBody')
        ->with($array)
        ->andReturn($request2);
    
    $requestHandler->shouldReceive('handle')
        ->with($request2)
        ->andReturn($response);
    
    $middleware = new JsonRequestParserMiddleware(['#^application\/vnd\.api\+json(;|$)#iD', '#^application\/json(;|$)#iD']);
    expect($middleware->process($request1, $requestHandler))->toBe($response);
});

test('it cannot parse invalid json', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $body = Mockery::mock(StreamInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    
    $body->shouldReceive('getContents')
        ->andReturn('{invalid,json...code}');
    
    $request->shouldReceive('getBody')
        ->andReturn($body);
    $request->shouldReceive('getHeaderLine')
        ->with('Content-Type')
        ->andReturn('application/json');
    
    $middleware = new JsonRequestParserMiddleware();
    expect(fn() => $middleware->process($request, $requestHandler))->toThrow(InvalidArgumentException::class);
});

test('it can do nothing', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    
    $request->shouldReceive('getHeaderLine')
        ->with('Content-Type')
        ->andReturn('text/html');
    
    $requestHandler->shouldReceive('handle')
        ->with($request)
        ->andReturn($response);
    
    $middleware = new JsonRequestParserMiddleware();
    expect($middleware->process($request, $requestHandler))->toBe($response);
}); 