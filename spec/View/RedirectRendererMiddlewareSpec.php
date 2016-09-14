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

    /** @var  ResponseInterface */
    private $response;

    public function let(RendererInterface $renderer, ResponseInterface $response)
    {
        $this->renderer = $renderer;
        $this->response = $response;
        $this->beConstructedWith($renderer, $response);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RedirectRendererMiddleware::class);
    }

    public function it_can_render_a_redirect(
        ServerRequestInterface $request,
        ResponseInterface $response2,
        ResponseInterface $response3,
        ViewInterface $view
    )
    {
        $redirectTo = '/some/other/path';

        $view->getStatusCode()->willReturn(302);
        $view->getHeaders()->willReturn(['Location' => $redirectTo]);

        $this->renderer->render($request, $view)->shouldNotBeCalled();
        $this->response->withHeader('Location', $redirectTo)->willReturn($response2);
        $response2->withStatus(302)->willReturn($response3);
        $this->render($request, $view)->shouldBe($response3);
    }

    public function it_cannot_render_without_location_header(
        ServerRequestInterface $request,
        ViewInterface $view
    )
    {
        $view->getStatusCode()->willReturn(302);
        $view->getHeaders()->willReturn([]);

        $this->renderer->render($request, $view)->shouldNotBeCalled();
        $this->shouldThrow(\UnderflowException::class)->duringRender($request, $view);
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
