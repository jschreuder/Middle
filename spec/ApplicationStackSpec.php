<?php

namespace spec\jschreuder\Middle;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use jschreuder\Middle\ApplicationStack;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  ApplicationStack */
class ApplicationStackSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ApplicationStack::class);
    }

    public function it_will_error_on_invalid_middleware()
    {
        $this->beConstructedWith(['not-a-middleware']);
        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    public function it_will_clone_on_with_middleware(
        ServerMiddlewareInterface $middleware1,
        ServerMiddlewareInterface $middleware2,
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2
    )
    {
        $this->beConstructedWith([$middleware1]);
        $clone = $this->withMiddleware($middleware2);

        $middleware1->process($request, new Argument\Token\TypeToken(DelegateInterface::class))
            ->willReturn($response1);
        $middleware2->process($request, new Argument\Token\TypeToken(DelegateInterface::class))
            ->willReturn($response2);

        $this->process($request)->shouldReturn($response1);
        $clone->process($request)->shouldReturn($response2);
    }

    public function it_will_clone_on_without_middleware(
        ServerMiddlewareInterface $middleware1,
        ServerMiddlewareInterface $middleware2,
        ServerRequestInterface $request,
        ResponseInterface $response1,
        ResponseInterface $response2
    )
    {
        $this->beConstructedWith([$middleware1, $middleware2]);
        $clone = $this->withoutMiddleware($middleware2);

        $middleware1->process($request, new Argument\Token\TypeToken(DelegateInterface::class))
            ->willReturn($response1);
        $middleware2->process($request, new Argument\Token\TypeToken(DelegateInterface::class))
            ->willReturn($response2);

        $this->process($request)->shouldReturn($response2);
        $clone->process($request)->shouldReturn($response1);
    }

    public function it_will_error_on_empty_stack(ServerRequestInterface $request)
    {
        $this->shouldThrow(\RuntimeException::class)->duringProcess($request);
    }
}
