<?php declare(strict_types=1);

namespace jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ControllerInterface $fallbackController
    )
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
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

        return $requestHandler->handle($request);
    }
}
