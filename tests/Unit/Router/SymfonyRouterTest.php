<?php

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\NoRouteMatch;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\SymfonyUrlGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

beforeEach(function () {
    $this->baseUrl = 'http://some.ho.st';
    $this->router = new SymfonyRouter($this->baseUrl);
});

test('it can be initialized', function () {
    expect($this->router)->toBeInstanceOf(SymfonyRouter::class);
});


test('it can be initialized with premade Symfony instances', function () {
    $routeCollection = Mockery::mock(RouteCollection::class);
    $routeGenerator = new SymfonyUrlGenerator(Mockery::mock(UrlGenerator::class));
    $router = new SymfonyRouter($this->baseUrl, $routeCollection, $routeGenerator);

    expect($router)->toBeInstanceOf(SymfonyRouter::class);
});

test('it can handle parsing a non match', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn('GET');
    $uri->shouldReceive('getScheme')->andReturn('http');
    $uri->shouldReceive('getHost')->andReturn('some.ho.st');
    $uri->shouldReceive('getPath')->andReturn('/some/path');
    $uri->shouldReceive('getQuery')->andReturn('');
    
    $routeMatch = $this->router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(NoRouteMatch::class)
        ->and($routeMatch->isMatch())->toBeFalse();
});

test('it has a url generator', function () {
    expect($this->router->getGenerator())->toBeInstanceOf(SymfonyUrlGenerator::class);
});

test('it can take a routing provider', function () {
    $routingProvider = Mockery::mock(RoutingProviderInterface::class);
    
    $routingProvider->shouldReceive('registerRoutes')
        ->with($this->router)
        ->once();
    
    $this->router->registerRoutes($routingProvider);
});

function testRoute($router, $request, $uri, $path, $method, $scheme, $host) {
    $request->shouldReceive('getUri')->andReturn($uri);
    $request->shouldReceive('getMethod')->andReturn($method);
    $uri->shouldReceive('getScheme')->andReturn($scheme);
    $uri->shouldReceive('getHost')->andReturn($host);
    $uri->shouldReceive('getPath')->andReturn($path);
    $uri->shouldReceive('getQuery')->andReturn('');
    
    $routeMatch = $router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())->toBeTrue()
        ->and($routeMatch->getController())->toBeInstanceOf(ControllerInterface::class);
}

test('it can register a get route', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->get($name, $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'GET', 'http', 'what.a.host');
});

test('it can register a post route', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->post($name, $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'POST', 'http', 'what.a.host');
});

test('it can register a put route', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->put($name, $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'PUT', 'http', 'what.a.host');
});

test('it can register a patch route', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->patch($name, $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'PATCH', 'http', 'what.a.host');
});

test('it can register a delete route', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->delete($name, $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'DELETE', 'http', 'what.a.host');
});

test('it can register other method routes', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $this->router->match($name, 'GET|POST|TEST', $path, $controllerFactory, $defaults, $requirements);
    testRoute($this->router, $request, $uri, '/path', 'TEST', 'http', 'what.a.host');
});

test('it can use a config callback', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);
    
    $controllerFactory = fn() => $controller;
    $name = 'name';
    $path = '/path';
    $defaults = ['default' => 42];
    $requirements = ['required' => 'a.towel'];
    
    $configCallback = function (Route $route) {
        $route->setHost('api.example.com');
    };
    
    $this->router->match($name, 'GET', $path, $controllerFactory, $defaults, $requirements, $configCallback);
    testRoute($this->router, $request, $uri, '/path', 'GET', 'http', 'api.example.com');
}); 