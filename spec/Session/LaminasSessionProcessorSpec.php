<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\SessionInterface;
use jschreuder\Middle\Session\LaminasSessionProcessor;
use Laminas\Session\Config\ConfigInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class LaminasSessionProcessorSpec extends ObjectBehavior
{
    /** @var  ConfigInterface */
    private $laminasSessionConfig;

    public function let(ConfigInterface $laminasSessionConfig)
    {
        $this->laminasSessionConfig = $laminasSessionConfig;
        $this->beConstructedWith($laminasSessionConfig);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LaminasSessionProcessor::class);
    }

    public function it_can_process_a_request(
        UriInterface $uri,
        ServerRequestInterface $request1,
        ServerRequestInterface $request2
    )
    {
        $uri->getHost()->willReturn('localhost');
        $request1->getUri()->willReturn($uri);
        $request1->withAttribute('session', new Argument\Token\TypeToken(SessionInterface::class))
            ->willReturn($request2);
        $this->processRequest($request1)->shouldReturn($request2);
    }

    public function it_can_process_a_response(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $this->processResponse($request, $response)->shouldReturn($response);
    }
}
