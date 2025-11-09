<?php

use jschreuder\Middle\Router\SymfonyUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;

test("it can be initialized", function () {
    $generator = Mockery::mock(UrlGenerator::class);

    $urlGenerator = new SymfonyUrlGenerator($generator);

    expect($urlGenerator)->toBeInstanceOf(SymfonyUrlGenerator::class);
});

test("it can create a path", function () {
    $generator = Mockery::mock(UrlGenerator::class);
    $name = "some.path";
    $options = ["another" => "time"];
    $result = "/some/path/another/time";

    $generator
        ->shouldReceive("generate")
        ->with($name, $options, UrlGenerator::ABSOLUTE_PATH)
        ->andReturn($result);

    $urlGenerator = new SymfonyUrlGenerator($generator);
    expect($urlGenerator->generatePath($name, $options))->toBe($result);
});

test("it can create a url", function () {
    $generator = Mockery::mock(UrlGenerator::class);
    $name = "some.path";
    $options = ["another" => "time"];
    $result = "http://with.ho.st/some/path/another/time";

    $generator
        ->shouldReceive("generate")
        ->with($name, $options, UrlGenerator::ABSOLUTE_URL)
        ->andReturn($result);

    $urlGenerator = new SymfonyUrlGenerator($generator);
    expect($urlGenerator->generateUrl($name, $options))->toBe($result);
});
