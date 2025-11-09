<?php

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;

test("it can be initialized", function () {
    $name = "test";
    $controller = Mockery::mock(ControllerInterface::class);
    $attributes = ["answer" => 42];

    $routeMatch = new RouteMatch($name, $controller, $attributes);

    expect($routeMatch)->toBeInstanceOf(RouteMatch::class);
    expect($routeMatch->isMatch())->toBeTrue();
    expect($routeMatch->getName())->toBe($name);
    expect($routeMatch->getController())->toBe($controller);
    expect($routeMatch->getAttributes())->toBe($attributes);
});
