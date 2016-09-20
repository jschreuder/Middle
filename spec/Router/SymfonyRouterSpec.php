<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;
use jschreuder\Middle\Router\RoutingProviderInterface;
use jschreuder\Middle\Router\SymfonyRouter;
use jschreuder\Middle\Router\SymfonyUrlGenerator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/** @mixin  SymfonyRouter */
class SymfonyRouterSpec extends ObjectBehavior
{
    /** @var  string */
    private $baseUrl = 'http://some.ho.st';

    public function let()
    {
        $this->beConstructedWith($this->baseUrl);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SymfonyRouter::class);
    }

    public function it_can_handle_parsing_a_non_match(ServerRequestInterface $request, UriInterface $uri)
    {
        $request->getUri()->willReturn($uri);
        $request->getMethod()->willReturn('GET');
        $uri->getScheme()->willReturn('http');
        $uri->getHost()->willReturn('some.ho.st');
        $uri->getPath()->willReturn('/some/path');
        $uri->getQuery()->willReturn('');

        $routeMatch = $this->parseRequest($request);
        $routeMatch->shouldHaveType(RouteMatch::class);
        $routeMatch->isMatch()->shouldReturn(false);
    }

    public function it_has_a_url_generator()
    {
        $this->getGenerator()->shouldHaveType(SymfonyUrlGenerator::class);
    }

    public function it_can_take_a_routing_provider(RoutingProviderInterface $routingProvider)
    {
        $routingProvider->registerRoutes($this)->shouldBeCalled();
        $this->registerRoutes($routingProvider);
    }

    public function test_route(
        ServerRequestInterface $request,
        UriInterface $uri,
        string $path,
        string $method,
        string $scheme,
        string $host
    )
    {
        $request->getUri()->willReturn($uri);
        $request->getMethod()->willReturn($method);
        $uri->getScheme()->willReturn($scheme);
        $uri->getHost()->willReturn($host);
        $uri->getPath()->willReturn($path);
        $uri->getQuery()->willReturn('');

        $routeMatch = $this->parseRequest($request);
        $routeMatch->shouldHaveType(RouteMatch::class);
        $routeMatch->isMatch()->shouldReturn(true);
    }

    public function it_can_register_a_get_route(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->get($name, $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'GET', 'http', 'what.a.host');
    }

    public function it_can_register_a_post_route(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->post($name, $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'POST', 'http', 'what.a.host');
    }

    public function it_can_register_a_put_route(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->put($name, $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'PUT', 'http', 'what.a.host');
    }

    public function it_can_register_a_patch_route(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->patch($name, $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'PATCH', 'http', 'what.a.host');
    }

    public function it_can_register_a_delete_route(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->delete($name, $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'DELETE', 'http', 'what.a.host');
    }

    public function it_can_register_other_method_routes(ServerRequestInterface $request, UriInterface $uri, ControllerInterface $controller)
    {
        $name = 'name';
        $path = '/path';
        $defaults = ['default' => 42];
        $requirements = ['required' => 'a.towel'];
        $this->match($name, 'GET|POST|TEST', $path, $controller, $defaults, $requirements);

        $this->test_route($request, $uri, '/path', 'TEST', 'http', 'what.a.host');
    }
}
