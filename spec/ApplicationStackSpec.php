<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\ApplicationStack;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApplicationStackSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ApplicationStack::class);
    }

    public function it_will_clone_on_with_middleware(
        MiddlewareInterface $middleware1,
        MiddlewareInterface $middleware2,
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2
    )
    {
        $this->beConstructedWith($middleware1);
        $clone = $this->withMiddleware($middleware2);

        $middleware1->process($request, new Argument\Token\TypeToken(RequestHandlerInterface::class))
            ->willReturn($response1);
        $middleware2->process($request, new Argument\Token\TypeToken(RequestHandlerInterface::class))
            ->willReturn($response2);

        $this->process($request)->shouldReturn($response1);
        $clone->process($request)->shouldReturn($response2);
    }

    public function it_will_clone_on_without_middleware(
        MiddlewareInterface $middleware1,
        MiddlewareInterface $middleware2,
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2
    )
    {
        $this->beConstructedWith($middleware1, $middleware2);
        $clone = $this->withoutMiddleware($middleware2);

        $middleware1->process($request, new Argument\Token\TypeToken(RequestHandlerInterface::class))
            ->willReturn($response1);
        $middleware2->process($request, new Argument\Token\TypeToken(RequestHandlerInterface::class))
            ->willReturn($response2);

        $this->process($request)->shouldReturn($response2);
        $clone->process($request)->shouldReturn($response1);
    }

    public function it_will_error_on_empty_stack(ServerRequestInterface $request)
    {
        $this->shouldThrow(\RuntimeException::class)->duringProcess($request);
    }
}
