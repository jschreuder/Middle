<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RoutingMiddleware implements ServerMiddlewareInterface
{
    /** @var  RouterInterface */
    private $router;

    /** @var  ControllerInterface */
    private $fallbackController;

    public function __construct(
        RouterInterface $router,
        ControllerInterface $fallbackController
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

        return $delegate->process($request);
    }
}
