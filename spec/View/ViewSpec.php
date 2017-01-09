<?php

namespace spec\jschreuder\Middle\View;

use jschreuder\Middle\View\View;
use jschreuder\Middle\View\ViewInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ViewSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $template = 'template.twig',
            $parameters = ['answer' => 42]
        );

        $this->shouldHaveType(View::class);

        $this->getStatusCode()->shouldReturn(200);
        $this->getHeaders()->shouldReturn([]);
        $this->getContentType()->shouldReturn(ViewInterface::CONTENT_TYPE_HTML);
        $this->getTemplate()->shouldReturn($template);
        $this->getParameters()->shouldReturn($parameters);
    }

    public function it_is_initializable_with_more_params()
    {
        $this->beConstructedWith(
            $template = 'template.twig',
            $parameters = ['answer' => 42],
            $statusCode = 418,
            $contentType = ViewInterface::CONTENT_TYPE_JSON,
            $headers = ['Not-A-Real-Header' => 'But-Fun-All-The-Same']
        );

        $this->shouldHaveType(View::class);

        $this->getStatusCode()->shouldReturn($statusCode);
        $this->getHeaders()->shouldReturn($headers);
        $this->getContentType()->shouldReturn($contentType);
        $this->getTemplate()->shouldReturn($template);
        $this->getParameters()->shouldReturn($parameters);
    }

    public function it_can_modify_headers_and_parameters()
    {
        $this->beConstructedWith(
            $template = 'template.twig',
            $parameters = ['answer' => 42]
        );

        $this->setHeader('Location', '/joe');
        $this->getHeaders()->shouldReturn(['Location' => '/joe']);

        $this->setParameter('answer', 41);
        $this->getParameters()->shouldReturn(['answer' => 41]);
    }
}
