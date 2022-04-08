<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\ServerMiddleware\JsonRequestParserMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        RequestHandlerInterface $requestHandler,
        ResponseInterface $response
    )
    {
        $array = ['test' => 42, 'next' => 'best'];
        $body->getContents()->willReturn(json_encode($array));
        $request1->getBody()->willReturn($body);
        $request1->getHeaderLine('Content-Type')->willReturn('application/json');
        $request1->withParsedBody($array)->willReturn($request2);

        $requestHandler->handle($request2)->willReturn($response);

        $this->process($request1, $requestHandler)->shouldReturn($response);
    }

    public function it_can_parse_json_with_charset(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        StreamInterface $body,
        RequestHandlerInterface $requestHandler,
        ResponseInterface $response
    )
    {
        $array = ['test' => 42, 'next' => 'best'];
        $body->getContents()->willReturn(json_encode($array));
        $request1->getBody()->willReturn($body);
        $request1->getHeaderLine('Content-Type')->willReturn('application/json;charset=utf-8');
        $request1->withParsedBody($array)->willReturn($request2);

        $requestHandler->handle($request2)->willReturn($response);

        $this->process($request1, $requestHandler)->shouldReturn($response);
    }

    public function it_can_parse_non_default_json_content_types(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        StreamInterface $body,
        RequestHandlerInterface $requestHandler,
        ResponseInterface $response
    )
    {
        $this->beConstructedWith(['#^application\/vnd\.api\+json(;|$)#iD', '#^application\/json(;|$)#iD']);

        $array = ['test' => 42, 'next' => 'best'];
        $body->getContents()->willReturn(json_encode($array));
        $request1->getBody()->willReturn($body);
        $request1->getHeaderLine('Content-Type')->willReturn('application/vnd.api+json');
        $request1->withParsedBody($array)->willReturn($request2);

        $requestHandler->handle($request2)->willReturn($response);

        $this->process($request1, $requestHandler)->shouldReturn($response);
    }

    public function it_cannot_parse_invalid_json(
        ServerRequestInterface $request,
        StreamInterface $body,
        RequestHandlerInterface $requestHandler,
        ResponseInterface $response
    )
    {
        $body->getContents()->willReturn('{invalid,json...code}');
        $request->getBody()->willReturn($body);
        $request->getHeaderLine('Content-Type')->willReturn('application/json');

        $this->shouldThrow(\InvalidArgumentException::class)->duringProcess($request, $requestHandler);
    }

    public function it_can_do_nothing(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler,
        ResponseInterface $response
    )
    {
        $request->getHeaderLine('Content-Type')->willReturn('text/html');
        $requestHandler->handle($request)->willReturn($response);
        $this->process($request, $requestHandler)->shouldReturn($response);
    }
}
