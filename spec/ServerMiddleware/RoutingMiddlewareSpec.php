<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\ServerMiddleware\RoutingMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddlewareSpec extends ObjectBehavior
{
    /** @var  RouterInterface */
    private $router;

    /** @var  ControllerInterface */
    private $fallbackController;

    public function let(RouterInterface $router, ControllerInterface $fallbackController)
    {
        $this->router = $router;
        $this->fallbackController = $fallbackController;
        $this->beConstructedWith($router, $fallbackController);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RoutingMiddleware::class);
    }

    public function it_can_assign_a_controller_and_attributes(
        ControllerInterface $controller,
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ServerRequestInterface $request3,
        ServerRequestInterface $request4,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $attributes = ['v1' => 1, 'v2' => 2];
        $routeMatch = new RouteMatch(true, $controller->getWrappedObject(), $attributes);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $controller)->willReturn($request2);
        $request2->withAttribute('v1', $attributes['v1'])->willReturn($request3);
        $request3->withAttribute('v2', $attributes['v2'])->willReturn($request4);

        $requestHandler->handle($request4)->willReturn($response);

        $this->process($request1, $requestHandler)->shouldReturn($response);
    }

    public function it_can_assign_fallback_controller_without_a_match(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $routeMatch = new RouteMatch(false);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $this->fallbackController)->willReturn($request2);

        $requestHandler->handle($request2)->willReturn($response);

        $this->process($request1, $requestHandler)->shouldReturn($response);
    }
}
