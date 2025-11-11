<?php declare(strict_types=1);

namespace jschreuder\Middle\Router;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as SymfonyRoutingException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Closure;

final readonly class SymfonyRouter implements RouterInterface
{
    private RouteCollection $router;
    private SymfonyUrlGenerator $generator;

    public function __construct(
        private string $baseUrl,
        ?RouteCollection $router = null,
        ?SymfonyUrlGenerator $generator = null,
    ) {
        $this->router = $router ?? new RouteCollection();
        $this->generator =
            $generator ??
            new SymfonyUrlGenerator(
                new UrlGenerator(
                    $this->router,
                    new RequestContext(
                        $this->baseUrl,
                        "GET",
                        parse_url($this->baseUrl, PHP_URL_HOST) ?: "localhost",
                    ),
                ),
            );
    }

    #[\Override]
    public function parseRequest(
        ServerRequestInterface $request,
    ): RouteMatchInterface {
        try {
            $matcher = new UrlMatcher(
                $this->router,
                $this->getRequestContext($request),
            );
            $relativePath = $this->stripBasePathFromRequestPath(
                $request->getUri()->getPath(),
            );
            $routeMatch = $matcher->match($relativePath);

            return new RouteMatch(
                $routeMatch["_route"],
                $routeMatch["controller"](),
                array_diff_key(
                    $routeMatch,
                    array_flip(["controller", "_route"]),
                ),
            );
        } catch (SymfonyRoutingException $exception) {
            return new NoRouteMatch();
        }
    }

    private function getRequestContext(
        ServerRequestInterface $request,
    ): RequestContext {
        return new RequestContext(
            $this->baseUrl,
            $request->getMethod(),
            $request->getUri()->getHost(),
            $request->getUri()->getScheme(),
            80,
            443,
            $request->getUri()->getPath(),
            $request->getUri()->getQuery(),
        );
    }

    private function stripBasePathFromRequestPath(string $requestPath): string
    {
        $basePath = parse_url($this->baseUrl, PHP_URL_PATH) ?: "/";

        // If basePath is just '/', return the request path as-is
        if ($basePath === "/") {
            return $requestPath;
        }

        // If the request path starts with the base path, strip it
        if (str_starts_with($requestPath, $basePath)) {
            $relativePath = substr($requestPath, strlen($basePath));
            // Ensure the relative path starts with '/'
            return $relativePath === "" ? "/" : $relativePath;
        }

        // If no stripping occurred, return the request path as-is
        return $requestPath;
    }

    #[\Override]
    public function getGenerator(): UrlGeneratorInterface
    {
        return $this->generator;
    }

    #[\Override]
    public function registerRoutes(
        RoutingProviderInterface $routingProvider,
    ): void {
        $routingProvider->registerRoutes($this);
    }

    #[\Override]
    public function match(
        string $name,
        string $methods,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $symfonyRoute = new Route($path, $defaults, $requirements);
        $symfonyRoute
            ->setMethods(explode("|", $methods))
            ->setDefault("controller", $controllerFactory);
        if ($configCallback) {
            $configCallback($symfonyRoute);
        }
        $this->router->add($name, $symfonyRoute);
    }

    #[\Override]
    public function get(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $this->match(
            $name,
            "GET",
            $path,
            $controllerFactory,
            $defaults,
            $requirements,
            $configCallback,
        );
    }

    #[\Override]
    public function post(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $this->match(
            $name,
            "POST",
            $path,
            $controllerFactory,
            $defaults,
            $requirements,
            $configCallback,
        );
    }

    #[\Override]
    public function put(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $this->match(
            $name,
            "PUT",
            $path,
            $controllerFactory,
            $defaults,
            $requirements,
            $configCallback,
        );
    }

    #[\Override]
    public function patch(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $this->match(
            $name,
            "PATCH",
            $path,
            $controllerFactory,
            $defaults,
            $requirements,
            $configCallback,
        );
    }

    #[\Override]
    public function delete(
        string $name,
        string $path,
        callable $controllerFactory,
        array $defaults = [],
        array $requirements = [],
        ?Closure $configCallback = null,
    ): void {
        $this->match(
            $name,
            "DELETE",
            $path,
            $controllerFactory,
            $defaults,
            $requirements,
            $configCallback,
        );
    }
}
