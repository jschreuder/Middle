<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

use jschreuder\Middle\DelegateInterface;
use jschreuder\Middle\HttpMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RoutingMiddleware implements HttpMiddlewareInterface
{
    /** @var  RouterInterface */
    private $router;

    /** @var  callable */
    private $fallbackController;

    public function __construct(
        RouterInterface $router,
        callable $fallbackController
    )
    {
        $this->router = $router;
        $this->fallbackController = $fallbackController;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $routeMatch = $this->router->parseRequest($request);

        if ($routeMatch->isMatch()) {
            // Register Controller to the request object
            $request = $request->withAttribute('controller', $routeMatch->getController());

            // Add all routing attributes to request
            foreach ($routeMatch->getAttributes() as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }
        } else {
            $request = $request->withAttribute('controller', $this->fallbackController);
        }

        return $delegate->next($request);
    }
}
