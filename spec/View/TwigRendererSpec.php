<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\TwigRenderer;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

/** @mixin  TwigRenderer */
class TwigRendererSpec extends ObjectBehavior
{
    /** @var  \Twig_Environment */
    private $twig;

    public function let(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->beConstructedWith($twig);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TwigRenderer::class);
    }

    public function it_can_render_a_template(ServerRequestInterface $request, ViewInterface $view)
    {
        $view->getContentType()->willReturn(ViewInterface::CONTENT_TYPE_HTML);
        $view->getStatusCode()->willReturn(201);
        $view->getHeaders()->willReturn([]);
        $view->getTemplate()->willReturn($template = 'template.twig');
        $view->getParameters()->willReturn($params = ['answer' => 42]);

        $rendered = '<strong>The answer is: </strong><em>42</em>';
        $this->twig->render($template, $params)->willReturn($rendered);

        $response = $this->render($request, $view);
        $response->shouldHaveType(HtmlResponse::class);
        $response->getBody()->getContents()->shouldReturn($rendered);
    }
}
