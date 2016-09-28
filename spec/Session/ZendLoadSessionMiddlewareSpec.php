<?php

namespace spec\jschreuder\Middle\Session;

use Interop\Http\Middleware\DelegateInterface;
use jschreuder\Middle\Session\ZendLoadSessionMiddleware;
use jschreuder\Middle\Session\SessionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/** @mixin  ZendLoadSessionMiddleware */
class ZendLoadSessionMiddlewareSpec extends ObjectBehavior
{
    /** @var  int */
    private $cookieLifetime = 0;

    public function let()
    {
        $this->beConstructedWith($this->cookieLifetime);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ZendLoadSessionMiddleware::class);
    }

    public function it_can_execute_a_request(
        ServerRequestInterface $request1,
        UriInterface $uri,
        ServerRequestInterface $request2,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request1->getUri()->willReturn($uri);
        $request1->withAttribute('session', new Argument\Token\TypeToken(SessionInterface::class))
            ->willReturn($request2);
        $uri->getHost()->willReturn('some.hostna.me');

        $delegate->process($request2)->willReturn($response);
        $this->process($request1, $delegate)->shouldReturn($response);
    }
}
