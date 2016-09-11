<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\ApplicationInterface;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\RoutingMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  RoutingMiddleware */
class RoutingMiddlewareSpec extends ObjectBehavior
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  RouterInterface */
    private $router;

    /** @var  callable */
    private $fallbackController;

    public function let(ApplicationInterface $application, RouterInterface $router)
    {
        $this->application = $application;
        $this->router = $router;
        $this->fallbackController = function () {};
        $this->beConstructedWith($application, $router, $this->fallbackController);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RoutingMiddleware::class);
    }

    public function it_can_assign_a_controller_and_attributes(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ServerRequestInterface $request3,
        ServerRequestInterface $request4,
        ResponseInterface $response
    ) {
        $controller = function () {};
        $attributes = ['v1' => 1, 'v2' => 2];
        $routeMatch = new RouteMatch(true, $controller, $attributes);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $controller)->willReturn($request2);
        $request2->withAttribute('v1', $attributes['v1'])->willReturn($request3);
        $request3->withAttribute('v2', $attributes['v2'])->willReturn($request4);

        $this->application->execute($request4)->willReturn($response);

        $this->execute($request1)->shouldReturn($response);
    }

    public function it_can_assign_fallback_controller_without_a_match(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response
    ) {
        $routeMatch = new RouteMatch(false);
        $this->router->parseRequest($request1)->willReturn($routeMatch);

        $request1->withAttribute('controller', $this->fallbackController)->willReturn($request2);

        $this->application->execute($request2)->willReturn($response);

        $this->execute($request1)->shouldReturn($response);
    }
}
