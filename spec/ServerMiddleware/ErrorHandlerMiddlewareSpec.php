<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ErrorHandlerMiddlewareSpec extends ObjectBehavior
{
    /** @var  LoggerInterface */
    private $logger;

    /** @var  ControllerInterface */
    private $errorController;

    public function let(LoggerInterface $logger, ControllerInterface $controller)
    {
        $this->logger = $logger;
        $this->errorController = $controller;
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
        $delegate->process($request)->willReturn($response);
        $this->logger->alert(new Argument\Token\AnyValueToken(), new Argument\Token\AnyValueToken())
            ->shouldNotBeCalled();
        $this->process($request, $delegate)->shouldReturn($response);
    }

    public function it_will_log_and_process_exception_when_thrown(
        ServerRequestInterface $request,
        ServerRequestInterface $request2,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $exception = new \RuntimeException($msg = uniqid(), $code = 418);
        $delegate->process($request)->willThrow($exception);
        $this->logger->alert($msg, new Argument\Token\ArrayEntryToken('file', __FILE__))->shouldBeCalled();

        $request->withAttribute('error', $exception)->willReturn($request2);
        $this->errorController->execute($request2)->willReturn($response);

        $this->process($request, $delegate)->shouldReturn($response);
    }
}
