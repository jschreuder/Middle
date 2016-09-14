<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\TwigRenderer;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/** @mixin  TwigRenderer */
class TwigRendererSpec extends ObjectBehavior
{
    /** @var  \Twig_Environment */
    private $twig;

    /** @var  ResponseInterface */
    private $baseResponse;

    public function let(\Twig_Environment $twig, ResponseInterface $baseResponse)
    {
        $this->twig = $twig;
        $this->baseResponse = $baseResponse;
        $this->beConstructedWith($twig, $baseResponse);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TwigRenderer::class);
    }

    public function it_can_render_a_template(
        ServerRequestInterface $request,
        ViewInterface $view,
        StreamInterface $stream,
        ResponseInterface $response2
    )
    {
        $view->getContentType()->willReturn(ViewInterface::CONTENT_TYPE_HTML);
        $view->getStatusCode()->willReturn(201);
        $view->getHeaders()->willReturn([]);
        $view->getTemplate()->willReturn($template = 'template.twig');
        $view->getParameters()->willReturn($params = ['answer' => 42]);

        $rendered = '<strong>The answer is: </strong><em>42</em>';
        $this->twig->render($template, $params)->willReturn($rendered);

        $this->baseResponse->withHeader('Content-Type', 'text/html; charset=utf-8')->willReturn($response2);
        $response2->getBody()->willReturn($stream);
        $stream->write($rendered)->shouldBeCalled();
        $stream->rewind()->shouldBeCalled();

        $this->render($request, $view)->shouldReturn($response2);
    }
}
