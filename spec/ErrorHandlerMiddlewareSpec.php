<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\DelegateInterface;
use jschreuder\Middle\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/** @mixin  ErrorHandlerMiddleware */
class ErrorHandlerMiddlewareSpec extends ObjectBehavior
{
    /** @var  LoggerInterface */
    private $logger;

    /** @var  callable */
    private $errorController;

    public function let(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->errorController = function () {};
        $this->beConstructedWith($logger, $this->errorController);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ErrorHandlerMiddleware::class);
    }

    public function it_will_do_nothing_without_an_exception(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $delegate->next($request)->willReturn($response);
        $this->logger->alert(new Argument\Token\AnyValueToken(), new Argument\Token\AnyValueToken())
            ->shouldNotBeCalled();
        $this->process($request, $delegate)->shouldReturn($response);
    }

    public function it_will_log_and_process_exception_when_thrown(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $exception = new \RuntimeException($msg = uniqid(), $code = 418);
        $delegate->next($request)->willThrow($exception);
        $this->logger->alert($msg, new Argument\Token\ArrayEntryToken('file', __FILE__))->shouldBeCalled();

        $this->errorController = function () use ($response) {
            return $response->getWrappedObject();
        };
        $this->beConstructedWith($this->logger, $this->errorController);

        $this->process($request, $delegate)->shouldReturn($response);
    }
}
