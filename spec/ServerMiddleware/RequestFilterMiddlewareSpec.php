<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\ServerMiddleware\RequestFilterMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  RequestFilterMiddleware */
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
        DelegateInterface $delegate
    )
    {
        $request1->getAttribute('controller')->willReturn($filter);
        $filter->filterRequest($request1)->willReturn($request2);
        $delegate->process($request2)->willReturn($response);
        $this->process($request1, $delegate)->shouldReturn($response);
    }

    public function it_can_do_nothing(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request->getAttribute('controller')->willReturn('trim');
        $delegate->process($request)->willReturn($response);
        $this->process($request, $delegate)->shouldReturn($response);
    }
}
