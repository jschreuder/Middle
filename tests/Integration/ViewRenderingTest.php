<?php

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\RedirectView;
use jschreuder\Middle\View\TwigRenderer;
use jschreuder\Middle\View\RedirectRendererMiddleware;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

test('it can render a view through the full rendering pipeline', function () {
    $loader = new ArrayLoader([
        'test.twig' => 'Hello {{ name }}!',
    ]);
    $twig = new Environment($loader);
    
    $responseFactory = Mockery::mock(\Psr\Http\Message\ResponseFactoryInterface::class);
    $response = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
    $stream = Mockery::mock(\Psr\Http\Message\StreamInterface::class);
    
    $responseFactory->shouldReceive('createResponse')->andReturn($response);
    $response->shouldReceive('withHeader')->andReturnSelf();
    $response->shouldReceive('getBody')->andReturn($stream);
    $stream->shouldReceive('rewind')->twice();
    $stream->shouldReceive('write')->with('Hello World!')->once();
    
    $renderer = new TwigRenderer($twig, $responseFactory);
    $redirectRenderer = new RedirectRendererMiddleware($renderer, $responseFactory);
    
    $view = new View('test.twig', ['name' => 'World']);
    $request = Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
    
    $result = $redirectRenderer->render($request, $view);
    expect($result)->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
});

test('it can handle redirects through the rendering pipeline', function () {
    $innerRenderer = Mockery::mock(\jschreuder\Middle\View\RendererInterface::class);
    $responseFactory = Mockery::mock(\Psr\Http\Message\ResponseFactoryInterface::class);
    $response = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
    
    $responseFactory->shouldReceive('createResponse')->with(302)->andReturn($response);
    $response->shouldReceive('withHeader')->with('Location', '/dashboard')->andReturnSelf();
    
    $innerRenderer->shouldNotReceive('render');
    
    $renderer = new RedirectRendererMiddleware($innerRenderer, $responseFactory);
    
    $view = new RedirectView('/dashboard', 302);
    $request = Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
    
    $result = $renderer->render($request, $view);
    expect($result)->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
});
