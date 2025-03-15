<?php

use jschreuder\Middle\View\RedirectRendererMiddleware;
use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

test('it can be initialized', function () {
    $renderer = Mockery::mock(RendererInterface::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $middleware = new RedirectRendererMiddleware($renderer, $responseFactory);
    
    expect($middleware)->toBeInstanceOf(RedirectRendererMiddleware::class);
});

test('it can render a redirect', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $response1 = Mockery::mock(ResponseInterface::class);
    $response2 = Mockery::mock(ResponseInterface::class);
    $view = Mockery::mock(ViewInterface::class);
    $renderer = Mockery::mock(RendererInterface::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $redirectTo = '/some/other/path';
    
    $view->shouldReceive('getStatusCode')->andReturn(302);
    $view->shouldReceive('getHeaders')->andReturn(['Location' => $redirectTo]);
    
    $responseFactory->shouldReceive('createResponse')
        ->with(302)
        ->andReturn($response1);
    
    $renderer->shouldNotReceive('render');
    
    $response1->shouldReceive('withHeader')
        ->with('Location', $redirectTo)
        ->andReturn($response2);
    
    $middleware = new RedirectRendererMiddleware($renderer, $responseFactory);
    expect($middleware->render($request, $view))->toBe($response2);
});

test('it cannot render without location header', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $view = Mockery::mock(ViewInterface::class);
    $renderer = Mockery::mock(RendererInterface::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $view->shouldReceive('getStatusCode')->andReturn(302);
    $view->shouldReceive('getHeaders')->andReturn([]);
    
    $renderer->shouldNotReceive('render');
    
    $middleware = new RedirectRendererMiddleware($renderer, $responseFactory);
    expect(fn() => $middleware->render($request, $view))->toThrow(UnderflowException::class);
});

test('it will pass on non redirect views', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $view = Mockery::mock(ViewInterface::class);
    $response = Mockery::mock(ResponseInterface::class);
    $renderer = Mockery::mock(RendererInterface::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $view->shouldReceive('getStatusCode')->andReturn(200);
    
    $renderer->shouldReceive('render')
        ->with($request, $view)
        ->andReturn($response);
    
    $middleware = new RedirectRendererMiddleware($renderer, $responseFactory);
    expect($middleware->render($request, $view))->toBe($response);
}); 