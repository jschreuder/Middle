<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as SymfonyRoutingException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class SymfonyRouter implements RouterInterface
{
    /** @var  string */
    private $baseUrl;

    /** @var  RouteCollection */
    private $router;

    /** @var  SymfonyUrlGenerator */
    private $generator;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;

        $this->router = new RouteCollection();
        $this->generator = new SymfonyUrlGenerator(
            new UrlGenerator(
                $this->router,
                new RequestContext($this->baseUrl, 'GET', parse_url($this->baseUrl, PHP_URL_HOST) ?: 'localhost')
            )
        );
    }

    public function parseRequest(ServerRequestInterface $request): RouteMatchInterface
    {
        try {
            $matcher = new UrlMatcher($this->router, $this->getRequestContext($request));
            $routeMatch = $matcher->match($request->getUri()->getPath());

            return new RouteMatch(
                true,
                ($routeMatch['controller'])(),
                array_diff_key($routeMatch, array_flip(['controller', '_route']))
            );
        } catch (SymfonyRoutingException $exception) {
            return new RouteMatch(false);
        }
    }

    private function getRequestContext(ServerRequestInterface $request): RequestContext
    {
        return new RequestContext(
            $this->baseUrl,
            $request->getMethod(),
            $request->getUri()->getHost(),
            $request->getUri()->getScheme(),
            80,
            443,
            $request->getUri()->getPath(),
            $request->getUri()->getQuery()
        );
    }

    public function getGenerator(): UrlGeneratorInterface
    {
        return $this->generator;
    }

    /** @return  void */
    public function registerRoutes(RoutingProviderInterface $routingProvider): void
    {
        $routingProvider->registerRoutes($this);
    }

    public function get(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        return $this->match($name, 'GET', $path, $controllerFactory, $defaults, $requirements);
    }

    public function match(
        string $name,
        string $methods,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        $route = new Route($path, $defaults, $requirements);
        $route->setMethods(explode('|', $methods))
            ->setDefault('controller', $controllerFactory);
        $this->router->add($name, $route);
        return $route;
    }

    public function post(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        return $this->match($name, 'POST', $path, $controllerFactory, $defaults, $requirements);
    }

    public function put(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        return $this->match($name, 'PUT', $path, $controllerFactory, $defaults, $requirements);
    }

    public function patch(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        return $this->match($name, 'PATCH', $path, $controllerFactory, $defaults, $requirements);
    }

    public function delete(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = []
    ): Route
    {
        return $this->match($name, 'DELETE', $path, $controllerFactory, $defaults, $requirements);
    }
}
