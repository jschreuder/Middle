<?php

namespace spec\jschreuder\Middle;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use jschreuder\Middle\Delegate;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  Delegate */
class DelegateSpec extends ObjectBehavior
{
    public function it_can_call_next(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ServerMiddlewareInterface $middleware
    )
    {
        $stack = new \SplStack();
        $this->beConstructedWith($stack);
        $this->shouldHaveType(Delegate::class);
        $stack->push($middleware->getWrappedObject());

        $middleware->process($request, $this)->willReturn($response);
        $this->next($request)->shouldReturn($response);
    }

    public function it_can_call_next_with_3_middlewares(
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2,
        ResponseInterface $response3,
        ServerMiddlewareInterface $middleware1,
        ServerMiddlewareInterface $middleware2,
        ServerMiddlewareInterface $middleware3
    )
    {
        $stack = new \SplStack();
        $this->beConstructedWith($stack);
        $this->shouldHaveType(Delegate::class);

        $stack->push($middleware1->getWrappedObject());
        $stack->push($middleware2->getWrappedObject());
        $stack->push($middleware3->getWrappedObject());

        $middleware1->process($request, $this)->willReturn($response1);
        $middleware2->process($request, $this)->willReturn($response2);
        $middleware3->process($request, $this)->willReturn($response3);

        $this->next($request)->shouldReturn($response3);
        $this->next($request)->shouldReturn($response2);
        $this->next($request)->shouldReturn($response1);
    }

    public function it_will_error_when_called_too_many_times(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ServerMiddlewareInterface $middleware
    )
    {
        $stack = new \SplStack();
        $this->beConstructedWith($stack);
        $this->shouldHaveType(Delegate::class);
        $stack->push($middleware->getWrappedObject());

        $middleware->process($request, $this)->willReturn($response);
        $this->next($request)->shouldReturn($response);
        $this->shouldThrow(\RuntimeException::class)->duringNext($request);
    }
}
