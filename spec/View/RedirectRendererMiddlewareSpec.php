<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\RedirectRendererMiddleware;
use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

/** @mixin  RedirectRendererMiddleware */
class RedirectRendererMiddlewareSpec extends ObjectBehavior
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
        $this->shouldHaveType(RedirectRendererMiddleware::class);
    }

    public function it_can_render_a_redirect(
        ServerRequestInterface $request,
        ViewInterface $view
    )
    {
        $redirectTo = '/some/other/path';

        $view->getStatusCode()->willReturn(302);
        $view->getHeaders()->willReturn(['Location' => $redirectTo]);

        $this->renderer->render($request, $view)->shouldNotBeCalled();
        $response = $this->render($request, $view);
        $response->shouldHaveType(RedirectResponse::class);
    }

    public function it_will_pass_on_non_redirect_views(
        ServerRequestInterface $request,
        ViewInterface $view,
        ResponseInterface $response
    )
    {
        $view->getStatusCode()->willReturn(200);

        $this->renderer->render($request, $view)->willReturn($response);
        $this->render($request, $view)->shouldReturn($response);
    }
}
