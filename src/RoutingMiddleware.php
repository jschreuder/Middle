<?php declare(strict_types = 1);

namespace jschreuder\Middle;

use jschreuder\Middle\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RoutingMiddleware implements ApplicationInterface
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  RouterInterface */
    private $router;

    /** @var  callable */
    private $fallbackController;

    public function __construct(
        ApplicationInterface $application,
        RouterInterface $router,
        string $baseUrl,
        callable $fallbackController
    )
    {
        $this->application = $application;
        $this->router = $router;
        $this->fallbackController = $fallbackController;
    }

    public function execute(ServerRequestInterface $request) : ResponseInterface
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

        return $this->application->execute($request);
    }
}
