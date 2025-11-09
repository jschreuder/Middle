<?php

use jschreuder\Middle\Router\NoRouteMatch;

test("it can be initialized", function () {
    $noRoute = new NoRouteMatch();

    expect($noRoute)->toBeInstanceOf(NoRouteMatch::class);
    expect($noRoute->getName())->toBe("");
    expect($noRoute->isMatch())->toBeFalse();
});

test("it does not have a controller", function () {
    $noRoute = new NoRouteMatch();

    expect(fn() => $noRoute->getController())->toThrow(RuntimeException::class);
});

test("it does not have attributes", function () {
    $noRoute = new NoRouteMatch();

    expect(fn() => $noRoute->getAttributes())->toThrow(RuntimeException::class);
});
