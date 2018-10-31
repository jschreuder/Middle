<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\RequestHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument\Token\TypeToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerSpec extends ObjectBehavior
{
    public function it_can_call_process(
        ServerRequestInterface $request,
        ResponseInterface $response,
        MiddlewareInterface $middleware
    )
    {
        $stack = new \SplStack();
        $this->beConstructedWith($stack);
        $this->shouldHaveType(RequestHandler::class);
        $stack->push($middleware->getWrappedObject());

        $middleware->process($request, new TypeToken(RequestHandlerInterface::class))->willReturn($response);
        $this->handle($request)->shouldReturn($response);
    }

    public function it_can_call_process_with_multiple_middlewares(
        ServerRequestInterface $request,
        ResponseInterface $response,
        MiddlewareInterface $middleware1,
        MiddlewareInterface $middleware2,
        MiddlewareInterface $middleware3
    )
    {
        $stack = new \SplStack();
        $stack->push($middleware1->getWrappedObject());
        $stack->push($middleware2->getWrappedObject());
        $stack->push($middleware3->getWrappedObject());

        $this->beConstructedWith($stack);
        $this->shouldHaveType(RequestHandler::class);

        $middleware3->process($request, new TypeToken(RequestHandlerInterface::class))->willReturn($response);

        $this->handle($request)->shouldReturn($response);
    }

    public function it_cannot_call_process_twice(
        ServerRequestInterface $request,
        ResponseInterface $response,
        MiddlewareInterface $middleware
    )
    {
        $stack = new \SplStack();
        $stack->push($middleware->getWrappedObject());

        $this->beConstructedWith($stack);
        $this->shouldHaveType(RequestHandler::class);

        $middleware->process($request, new TypeToken(RequestHandlerInterface::class))->willReturn($response);

        $this->handle($request)->shouldReturn($response);
        $this->shouldThrow(\RuntimeException::class)->duringHandle($request);
    }

    public function it_will_error_when_called_on_empty_stack(
        ServerRequestInterface $request,
        ResponseInterface $response,
        MiddlewareInterface $middleware
    )
    {
        $stack = new \SplStack();
        $this->beConstructedWith($stack);
        $this->shouldHaveType(RequestHandler::class);
        $stack->push($middleware->getWrappedObject());

        $middleware->process($request, new TypeToken(RequestHandlerInterface::class))->willReturn($response);
        $this->handle($request)->shouldReturn($response);
        $this->shouldThrow(\RuntimeException::class)->duringHandle($request);
    }
}
