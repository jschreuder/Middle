<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /** Parses ServerRequest and returns the routing information */
    public function parseRequest(ServerRequestInterface $request): RouteMatchInterface;

    /** Returns UrlGenerator for reverse routing */
    public function getGenerator(): UrlGeneratorInterface;

    /** @return  void */
    public function registerRoutes(RoutingProviderInterface $routingProvider);

    /** Generic method for adding routes with arbitrary or multiple methods */
    public function match(
        string $name,
        string $methods,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );

    /** Add route for GET request */
    public function get(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );

    /** Add route for POST request */
    public function post(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );

    /** Add route for PUT request */
    public function put(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );

    /** Add route for PATCH request */
    public function patch(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );

    /** Add route for DELETE request */
    public function delete(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    );
}