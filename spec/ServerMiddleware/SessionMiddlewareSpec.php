<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\ServerMiddleware\SessionMiddleware;
use jschreuder\Middle\Session\SessionProcessorInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionMiddlewareSpec extends ObjectBehavior
{
    /** @var  SessionProcessorInterface */
    private $processor;

    public function let(SessionProcessorInterface $processor)
    {
        $this->processor = $processor;
        $this->beConstructedWith($processor);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SessionMiddleware::class);
    }

    public function it_can_process_a_request_and_response(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        ResponseInterface $response1,
        ResponseInterface $response2,
        DelegateInterface $delegate
    )
    {
        $this->processor->processRequest($request1)->willReturn($request2);
        $delegate->process($request2)->willReturn($response1);
        $this->processor->processResponse($request2, $response1)->willReturn($response2);
        $this->process($request1, $delegate)->shouldReturn($response2);
    }
}
