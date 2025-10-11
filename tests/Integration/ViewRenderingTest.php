<?php

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\RedirectView;
use jschreuder\Middle\View\TwigRenderer;
use jschreuder\Middle\View\RedirectRendererMiddleware;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

test('it can render a view through the full rendering pipeline', function () {
    $loader = new ArrayLoader([
        'test.twig' => 'Hello {{ name }}!',
    ]);
    $twig = new Environment($loader);
    
    $responseFactory = new ResponseFactory();
    
    $renderer = new TwigRenderer($twig, $responseFactory);
    $redirectRenderer = new RedirectRendererMiddleware($renderer, $responseFactory);
    
    $view = new View('test.twig', ['name' => 'World']);
    $request = new ServerRequest([], [], new Uri('http://localhost/twig'), 'GET');
    
    $result = $redirectRenderer->render($request, $view);
    expect($result)->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
    expect($result->getBody()->getContents())->toBe('Hello World!');
});

test('it can handle redirects through the rendering pipeline', function () {
    $innerRenderer = Mockery::mock(\jschreuder\Middle\View\RendererInterface::class);
    $innerRenderer->shouldNotReceive('render');

    $responseFactory = new ResponseFactory();
    
    $renderer = new RedirectRendererMiddleware($innerRenderer, $responseFactory);
    
    $view = new RedirectView('/dashboard', 302);
    $request = new ServerRequest([], [], new Uri('http://localhost/redirect'), 'GET');
    
    $result = $renderer->render($request, $view);
    expect($result)->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class);
    expect($result->getHeaderLine('Location'))->toBe('/dashboard');
});
