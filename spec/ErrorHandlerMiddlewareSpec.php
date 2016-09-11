<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\ApplicationInterface;
use jschreuder\Middle\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/** @mixin  ErrorHandlerMiddleware */
class ErrorHandlerMiddlewareSpec extends ObjectBehavior
{
    /** @var  ApplicationInterface */
    private $application;

    /** @var  LoggerInterface */
    private $logger;

    /** @var  callable */
    private $errorController;

    public function let(ApplicationInterface $application, LoggerInterface $logger)
    {
        $this->application = $application;
        $this->logger = $logger;
        $this->errorController = function () {};
        $this->beConstructedWith($application, $logger, $this->errorController);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ErrorHandlerMiddleware::class);
    }

    public function it_will_do_nothing_without_an_exception(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $this->application->execute($request)->willReturn($response);
        $this->logger->alert(new Argument\Token\AnyValueToken(), new Argument\Token\AnyValueToken())
            ->shouldNotBeCalled();
        $this->execute($request)->shouldReturn($response);
    }

    public function it_will_log_and_process_exception_when_thrown(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $exception = new \RuntimeException($msg = uniqid(), $code = 418);
        $this->application->execute($request)->willThrow($exception);
        $this->logger->alert($msg, new Argument\Token\ArrayEntryToken('file', __FILE__))->shouldBeCalled();

        $this->errorController = function () use ($response) {
            return $response->getWrappedObject();
        };
        $this->beConstructedWith($this->application, $this->logger, $this->errorController);

        $this->execute($request)->shouldReturn($response);
    }
}
