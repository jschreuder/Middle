<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\RedirectRendererMiddleware;
use jschreuder\Middle\View\RendererInterface;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Factory\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  RedirectRendererMiddleware */
class RedirectRendererMiddlewareSpec extends ObjectBehavior
{
    /** @var  RendererInterface */
    private $renderer;

    /** @var  ResponseFactoryInterface */
    private $responseFactory;

    public function let(RendererInterface $renderer, ResponseFactoryInterface $responseFactory)
    {
        $this->renderer = $renderer;
        $this->responseFactory = $responseFactory;
        $this->beConstructedWith($renderer, $responseFactory);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RedirectRendererMiddleware::class);
    }

    public function it_can_render_a_redirect(
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ViewInterface $view
    )
    {
        $redirectTo = '/some/other/path';

        $view->getStatusCode()->willReturn(302);
        $view->getHeaders()->willReturn(['Location' => $redirectTo]);

        $this->responseFactory->createResponse(302)->willReturn($response1);
        $this->renderer->render($request, $view)->shouldNotBeCalled();
        $response1->withHeader('Location', $redirectTo)->willReturn($response2);
        $this->render($request, $view)->shouldBe($response2);
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
