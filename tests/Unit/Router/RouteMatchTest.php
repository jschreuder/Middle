<?php

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;

test('it can be initialized', function () {
    $controller = Mockery::mock(ControllerInterface::class);
    $attributes = ['answer' => 42];
    
    $routeMatch = new RouteMatch($controller, $attributes);
    
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())->toBeTrue()
        ->and($routeMatch->getController())->toBe($controller)
        ->and($routeMatch->getAttributes())->toBe($attributes);
}); 