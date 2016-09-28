<?php

namespace spec\jschreuder\Middle\Session;

use Interop\Http\Middleware\DelegateInterface;
use jschreuder\Middle\Session\JwtLoadSessionMiddleware;
use jschreuder\Middle\Session\JwtToPsrMapperInterface;
use jschreuder\Middle\Session\SessionInterface;
use Lcobucci\JWT\Token;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  JwtLoadSessionMiddleware */
class JwtLoadSessionMiddlewareSpec extends ObjectBehavior
{
    /** @var  JwtToPsrMapperInterface */
    private $mapper;

    public function let(JwtToPsrMapperInterface $mapper)
    {
        $this->mapper = $mapper;
        $this->beConstructedWith($mapper);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(JwtLoadSessionMiddleware::class);
    }

    public function it_can_process_a_token(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response1,
        ResponseInterface $response2,
        DelegateInterface $delegate,
        Token $token,
        SessionInterface $session
    )
    {
        $this->mapper->parseToken($request1)->willReturn($token);
        $this->mapper->extractSessionContainer($token)->willReturn($session);
        $request1->withAttribute('session', $session)->willReturn($request2);
        $delegate->process($request2)->willReturn($response1);
        $this->mapper->appendToken($session, $response1, $token)->willReturn($response2);
        $this->process($request1, $delegate)->shouldReturn($response2);
    }

    public function it_can_process_without_token(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response1,
        ResponseInterface $response2,
        DelegateInterface $delegate,
        SessionInterface $session
    )
    {
        $this->mapper->parseToken($request1)->willReturn(null);
        $this->mapper->extractSessionContainer(null)->willReturn($session);
        $request1->withAttribute('session', $session)->willReturn($request2);
        $delegate->process($request2)->willReturn($response1);
        $this->mapper->appendToken($session, $response1, null)->willReturn($response2);
        $this->process($request1, $delegate)->shouldReturn($response2);
    }
}
