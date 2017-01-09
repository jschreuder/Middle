<?php

namespace spec\jschreuder\Middle\Router;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  RoutingMiddleware */
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
        DelegateInterface $delegate
    )
    {
        $attributes = ['v1' => 1, 'v2' => 2];
        $routeMatch = new RouteMatch(true, $controller->getWrappedObject(), $attributes);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $controller)->willReturn($request2);
        $request2->withAttribute('v1', $attributes['v1'])->willReturn($request3);
        $request3->withAttribute('v2', $attributes['v2'])->willReturn($request4);

        $delegate->process($request4)->willReturn($response);

        $this->process($request1, $delegate)->shouldReturn($response);
    }

    public function it_can_assign_fallback_controller_without_a_match(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $routeMatch = new RouteMatch(false);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $this->fallbackController)->willReturn($request2);

        $delegate->process($request2)->willReturn($response);

        $this->process($request1, $delegate)->shouldReturn($response);
    }
}
