<?php

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\ViewInterface;

test('it can be initialized with basic parameters', function () {
    $template = 'template.twig';
    $parameters = ['answer' => 42];
    
    $view = new View($template, $parameters);
    
    expect($view)
        ->toBeInstanceOf(View::class)
        ->and($view->getStatusCode())->toBe(200)
        ->and($view->getHeaders())->toBe([])
        ->and($view->getContentType())->toBe(ViewInterface::CONTENT_TYPE_HTML)
        ->and($view->getTemplate())->toBe($template)
        ->and($view->getParameters())->toBe($parameters);
});

test('it can be initialized with all parameters', function () {
    $template = 'template.twig';
    $parameters = ['answer' => 42];
    $statusCode = 418;
    $contentType = ViewInterface::CONTENT_TYPE_JSON;
    $headers = ['Not-A-Real-Header' => 'But-Fun-All-The-Same'];
    
    $view = new View($template, $parameters, $statusCode, $contentType, $headers);
    
    expect($view)
        ->toBeInstanceOf(View::class)
        ->and($view->getStatusCode())->toBe($statusCode)
        ->and($view->getHeaders())->toBe($headers)
        ->and($view->getContentType())->toBe($contentType)
        ->and($view->getTemplate())->toBe($template)
        ->and($view->getParameters())->toBe($parameters);
});

test('it can modify headers and parameters', function () {
    $view = new View('template.twig', ['answer' => 42]);
    
    $view->setHeader('Location', '/joe');
    expect($view->getHeaders())->toBe(['Location' => '/joe']);
    
    $view->setParameter('answer', 41);
    expect($view->getParameters())->toBe(['answer' => 41]);
}); 