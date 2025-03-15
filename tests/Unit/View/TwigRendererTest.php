<?php

use jschreuder\Middle\View\TwigRenderer;
use jschreuder\Middle\View\ViewInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Twig\Environment;

test('it can be initialized', function () {
    $twig = Mockery::mock(Environment::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $renderer = new TwigRenderer($twig, $responseFactory);
    
    expect($renderer)->toBeInstanceOf(TwigRenderer::class);
});

test('it can render a template', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $view = Mockery::mock(ViewInterface::class);
    $stream = Mockery::mock(StreamInterface::class);
    $response1 = Mockery::mock(ResponseInterface::class);
    $response2 = Mockery::mock(ResponseInterface::class);
    $twig = Mockery::mock(Environment::class);
    $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
    
    $template = 'template.twig';
    $params = ['answer' => 42];
    $rendered = '<strong>The answer is: </strong><em>42</em>';
    
    $view->shouldReceive('getContentType')->andReturn(ViewInterface::CONTENT_TYPE_HTML);
    $view->shouldReceive('getStatusCode')->andReturn(201);
    $view->shouldReceive('getHeaders')->andReturn([]);
    $view->shouldReceive('getTemplate')->andReturn($template);
    $view->shouldReceive('getParameters')->andReturn($params);
    
    $twig->shouldReceive('render')
        ->with($template, $params)
        ->andReturn($rendered);
    
    $responseFactory->shouldReceive('createResponse')->andReturn($response1);
    $response1->shouldReceive('withHeader')
        ->with('Content-Type', 'text/html; charset=utf-8')
        ->andReturn($response2);
    $response2->shouldReceive('getBody')->andReturn($stream);
    $stream->shouldReceive('write')->with($rendered)->once();
    $stream->shouldReceive('rewind')->twice();
    
    $renderer = new TwigRenderer($twig, $responseFactory);
    expect($renderer->render($request, $view))->toBe($response2);
}); 