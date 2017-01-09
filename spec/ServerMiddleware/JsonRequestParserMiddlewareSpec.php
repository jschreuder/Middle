<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class JsonRequestParserMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(JsonRequestParserMiddleware::class);
    }

    public function it_can_parse_json(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        StreamInterface $body,
        DelegateInterface $delegate,
        ResponseInterface $response
    )
    {
        $array = ['test' => 42, 'next' => 'best'];
        $body->getContents()->willReturn(json_encode($array));
        $request1->getBody()->willReturn($body);
        $request1->getHeaderLine('Content-Type')->willReturn('application/json');
        $request1->withParsedBody($array)->willReturn($request2);

        $delegate->process($request2)->willReturn($response);

        $this->process($request1, $delegate)->shouldReturn($response);
    }

    public function it_can_parse_non_default_json_content_types(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        StreamInterface $body,
        DelegateInterface $delegate,
        ResponseInterface $response
    )
    {
        $this->beConstructedWith(['application/vnd.api+json', 'application/json']);

        $array = ['test' => 42, 'next' => 'best'];
        $body->getContents()->willReturn(json_encode($array));
        $request1->getBody()->willReturn($body);
        $request1->getHeaderLine('Content-Type')->willReturn('application/vnd.api+json');
        $request1->withParsedBody($array)->willReturn($request2);

        $delegate->process($request2)->willReturn($response);

        $this->process($request1, $delegate)->shouldReturn($response);
    }

    public function it_can_do_nothing(
        ServerRequestInterface $request,
        DelegateInterface $delegate,
        ResponseInterface $response
    )
    {
        $request->getHeaderLine('Content-Type')->willReturn('text/html');
        $delegate->process($request)->willReturn($response);
        $this->process($request, $delegate)->shouldReturn($response);
    }
}
