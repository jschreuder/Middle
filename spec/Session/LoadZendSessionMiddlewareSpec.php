<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\ApplicationInterface;
use jschreuder\Middle\Session\LoadZendSessionMiddleware;
use jschreuder\Middle\Session\SessionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/** @mixin  LoadZendSessionMiddleware */
class LoadZendSessionMiddlewareSpec extends ObjectBehavior
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  int */
    private $cookieLifetime = 0;

    public function let(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->beConstructedWith($application, $this->cookieLifetime);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LoadZendSessionMiddleware::class);
    }

    public function it_can_execute_a_request(
        ServerRequestInterface $request1,
        UriInterface $uri,
        ServerRequestInterface $request2,
        ResponseInterface $response
    )
    {
        $request1->getUri()->willReturn($uri);
        $request1->withAttribute('session', new Argument\Token\TypeToken(SessionInterface::class))
            ->willReturn($request2);
        $uri->getHost()->willReturn('some.hostna.me');

        $this->application->execute($request2)->willReturn($response);
        $this->execute($request1)->shouldReturn($response);
    }
}
