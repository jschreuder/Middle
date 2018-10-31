<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestFilterMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RequestFilterMiddleware::class);
    }

    public function it_can_filter_a_request(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        RequestFilterInterface $filter,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $request1->getAttribute('controller')->willReturn($filter);
        $filter->filterRequest($request1)->willReturn($request2);
        $requestHandler->handle($request2)->willReturn($response);
        $this->process($request1, $requestHandler)->shouldReturn($response);
    }

    public function it_can_do_nothing(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $request->getAttribute('controller')->willReturn('trim');
        $requestHandler->handle($request)->willReturn($response);
        $this->process($request, $requestHandler)->shouldReturn($response);
    }
}
