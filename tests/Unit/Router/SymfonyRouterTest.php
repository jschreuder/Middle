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
    $this->baseUrl = "http://some.ho.st";
    $this->router = new SymfonyRouter($this->baseUrl);
});

test("it can be initialized", function () {
    expect($this->router)->toBeInstanceOf(SymfonyRouter::class);
});

test("it can be initialized with premade Symfony instances", function () {
    $routeCollection = Mockery::mock(RouteCollection::class);
    $routeGenerator = new SymfonyUrlGenerator(
        Mockery::mock(UrlGenerator::class),
    );
    $router = new SymfonyRouter(
        $this->baseUrl,
        $routeCollection,
        $routeGenerator,
    );

    expect($router)->toBeInstanceOf(SymfonyRouter::class);
});

test("it can handle parsing a non match", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);

    $request->shouldReceive("getUri")->andReturn($uri);
    $request->shouldReceive("getMethod")->andReturn("GET");
    $uri->shouldReceive("getScheme")->andReturn("http");
    $uri->shouldReceive("getHost")->andReturn("some.ho.st");
    $uri->shouldReceive("getPath")->andReturn("/some/path");
    $uri->shouldReceive("getQuery")->andReturn("");

    $routeMatch = $this->router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(NoRouteMatch::class)
        ->and($routeMatch->isMatch())
        ->toBeFalse();
});

test("it has a url generator", function () {
    expect($this->router->getGenerator())->toBeInstanceOf(
        SymfonyUrlGenerator::class,
    );
});

test("it can take a routing provider", function () {
    $routingProvider = Mockery::mock(RoutingProviderInterface::class);

    $routingProvider
        ->shouldReceive("registerRoutes")
        ->with($this->router)
        ->once();

    $this->router->registerRoutes($routingProvider);
});

function testRoute($router, $request, $uri, $path, $method, $scheme, $host)
{
    $request->shouldReceive("getUri")->andReturn($uri);
    $request->shouldReceive("getMethod")->andReturn($method);
    $uri->shouldReceive("getScheme")->andReturn($scheme);
    $uri->shouldReceive("getHost")->andReturn($host);
    $uri->shouldReceive("getPath")->andReturn($path);
    $uri->shouldReceive("getQuery")->andReturn("");

    $routeMatch = $router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())
        ->toBeTrue()
        ->and($routeMatch->getController())
        ->toBeInstanceOf(ControllerInterface::class);
}

test("it can register a get route", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->get(
        $name,
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "GET",
        "http",
        "what.a.host",
    );
});

test("it can register a post route", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->post(
        $name,
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "POST",
        "http",
        "what.a.host",
    );
});

test("it can register a put route", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->put(
        $name,
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "PUT",
        "http",
        "what.a.host",
    );
});

test("it can register a patch route", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->patch(
        $name,
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "PATCH",
        "http",
        "what.a.host",
    );
});

test("it can register a delete route", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->delete(
        $name,
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "DELETE",
        "http",
        "what.a.host",
    );
});

test("it can register other method routes", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $this->router->match(
        $name,
        "GET|POST|TEST",
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "TEST",
        "http",
        "what.a.host",
    );
});

test("it can use a config callback", function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $controllerFactory = fn() => $controller;
    $name = "name";
    $path = "/path";
    $defaults = ["default" => 42];
    $requirements = ["required" => "a.towel"];

    $configCallback = function (Route $route) {
        $route->setHost("api.example.com");
    };

    $this->router->match(
        $name,
        "GET",
        $path,
        $controllerFactory,
        $defaults,
        $requirements,
        $configCallback,
    );
    testRoute(
        $this->router,
        $request,
        $uri,
        "/path",
        "GET",
        "http",
        "api.example.com",
    );
});

// Tests for relative path parsing with baseUrl stripping
test(
    "it can parse relative paths with baseUrl containing file entry point",
    function () {
        $baseUrl = "http://localhost:8080/api.php";
        $router = new SymfonyRouter($baseUrl);
        $request = Mockery::mock(ServerRequestInterface::class);
        $uri = Mockery::mock(UriInterface::class);
        $controller = Mockery::mock(ControllerInterface::class);

        $router->get("test_route", "/some/path", fn() => $controller);

        $request->shouldReceive("getUri")->andReturn($uri);
        $request->shouldReceive("getMethod")->andReturn("GET");
        $uri->shouldReceive("getScheme")->andReturn("http");
        $uri->shouldReceive("getHost")->andReturn("localhost");
        $uri->shouldReceive("getPath")->andReturn("/api.php/some/path");
        $uri->shouldReceive("getQuery")->andReturn("");

        $routeMatch = $router->parseRequest($request);
        expect($routeMatch)
            ->toBeInstanceOf(RouteMatch::class)
            ->and($routeMatch->isMatch())
            ->toBeTrue();
    },
);

test("it can parse relative paths with simple root baseUrl", function () {
    $baseUrl = "http://localhost";
    $router = new SymfonyRouter($baseUrl);
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $router->get("test_route", "/some/path", fn() => $controller);

    $request->shouldReceive("getUri")->andReturn($uri);
    $request->shouldReceive("getMethod")->andReturn("GET");
    $uri->shouldReceive("getScheme")->andReturn("http");
    $uri->shouldReceive("getHost")->andReturn("localhost");
    $uri->shouldReceive("getPath")->andReturn("/some/path");
    $uri->shouldReceive("getQuery")->andReturn("");

    $routeMatch = $router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())
        ->toBeTrue();
});

test(
    "it can parse relative paths with baseUrl containing subdirectory",
    function () {
        $baseUrl = "http://example.com/api/v1";
        $router = new SymfonyRouter($baseUrl);
        $request = Mockery::mock(ServerRequestInterface::class);
        $uri = Mockery::mock(UriInterface::class);
        $controller = Mockery::mock(ControllerInterface::class);

        $router->get("test_route", "/users", fn() => $controller);

        $request->shouldReceive("getUri")->andReturn($uri);
        $request->shouldReceive("getMethod")->andReturn("GET");
        $uri->shouldReceive("getScheme")->andReturn("http");
        $uri->shouldReceive("getHost")->andReturn("example.com");
        $uri->shouldReceive("getPath")->andReturn("/api/v1/users");
        $uri->shouldReceive("getQuery")->andReturn("");

        $routeMatch = $router->parseRequest($request);
        expect($routeMatch)
            ->toBeInstanceOf(RouteMatch::class)
            ->and($routeMatch->isMatch())
            ->toBeTrue();
    },
);

test("it can parse relative paths with subdomain", function () {
    $baseUrl = "http://api.example.com";
    $router = new SymfonyRouter($baseUrl);
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $router->get("test_route", "/products", fn() => $controller);

    $request->shouldReceive("getUri")->andReturn($uri);
    $request->shouldReceive("getMethod")->andReturn("GET");
    $uri->shouldReceive("getScheme")->andReturn("http");
    $uri->shouldReceive("getHost")->andReturn("api.example.com");
    $uri->shouldReceive("getPath")->andReturn("/products");
    $uri->shouldReceive("getQuery")->andReturn("");

    $routeMatch = $router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())
        ->toBeTrue();
});

test(
    "it can parse relative paths with subdomain and subdirectory",
    function () {
        $baseUrl = "http://api.example.com/v2";
        $router = new SymfonyRouter($baseUrl);
        $request = Mockery::mock(ServerRequestInterface::class);
        $uri = Mockery::mock(UriInterface::class);
        $controller = Mockery::mock(ControllerInterface::class);

        $router->get("test_route", "/status", fn() => $controller);

        $request->shouldReceive("getUri")->andReturn($uri);
        $request->shouldReceive("getMethod")->andReturn("GET");
        $uri->shouldReceive("getScheme")->andReturn("http");
        $uri->shouldReceive("getHost")->andReturn("api.example.com");
        $uri->shouldReceive("getPath")->andReturn("/v2/status");
        $uri->shouldReceive("getQuery")->andReturn("");

        $routeMatch = $router->parseRequest($request);
        expect($routeMatch)
            ->toBeInstanceOf(RouteMatch::class)
            ->and($routeMatch->isMatch())
            ->toBeTrue();
    },
);

test("it can parse relative paths with nested routes", function () {
    $baseUrl = "http://localhost:9000/app";
    $router = new SymfonyRouter($baseUrl);
    $request = Mockery::mock(ServerRequestInterface::class);
    $uri = Mockery::mock(UriInterface::class);
    $controller = Mockery::mock(ControllerInterface::class);

    $router->get("test_route", "/api/v1/users/{id}", fn() => $controller, [
        "id" => "\\d+",
    ]);

    $request->shouldReceive("getUri")->andReturn($uri);
    $request->shouldReceive("getMethod")->andReturn("GET");
    $uri->shouldReceive("getScheme")->andReturn("http");
    $uri->shouldReceive("getHost")->andReturn("localhost");
    $uri->shouldReceive("getPath")->andReturn("/app/api/v1/users/123");
    $uri->shouldReceive("getQuery")->andReturn("");

    $routeMatch = $router->parseRequest($request);
    expect($routeMatch)
        ->toBeInstanceOf(RouteMatch::class)
        ->and($routeMatch->isMatch())
        ->toBeTrue()
        ->and($routeMatch->getAttributes())
        ->toBe(["id" => "123"]);
});
