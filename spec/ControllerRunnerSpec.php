<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\ControllerRunner;
use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  ControllerRunner */
class ControllerRunnerSpec extends ObjectBehavior
{
    /** @var  RendererInterface */
    private $renderer;

    public function let(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
        $this->beConstructedWith($renderer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ControllerRunner::class);
    }

    public function it_can_execute_a_controller(
        ServerRequestInterface $request,
        ViewInterface $view,
        ResponseInterface $response
    )
    {
        $controller = function () use ($view) {
            return $view->getWrappedObject();
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->renderer->render($request, $view)->willReturn($response);
        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_execute_a_controller_returning_a_psr_response(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $controller = function () use ($response) {
            return $response->getWrappedObject();
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->execute($request)->shouldReturn($response);
    }

    public function it_will_error_on_invalid_response(ServerRequestInterface $request)
    {
        $controller = function () {
            return 'an invalid response';
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->shouldThrow(\TypeError::class)->duringExecute($request);
    }

    public function it_will_error_on_invalid_response_on_views_with_no_renderer(
        ServerRequestInterface $request,
        ViewInterface $view
    )
    {
        $controller = function () use ($view) {
            return $view->getWrappedObject();
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->beConstructedWith();
        $this->shouldThrow(\TypeError::class)->duringExecute($request);
    }
}
