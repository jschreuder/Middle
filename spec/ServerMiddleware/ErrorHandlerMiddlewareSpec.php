<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\ServerMiddleware\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
        RequestHandlerInterface $requestHandler
    )
    {
        $requestHandler->handle($request)->willReturn($response);
        $this->logger->alert(new Argument\Token\AnyValueToken(), new Argument\Token\AnyValueToken())
            ->shouldNotBeCalled();
        $this->process($request, $requestHandler)->shouldReturn($response);
    }

    public function it_will_log_and_process_exception_when_thrown(
        ServerRequestInterface $request,
        ServerRequestInterface $request2,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $exception = new \RuntimeException($msg = uniqid(), $code = 418);
        $requestHandler->handle($request)->willThrow($exception);
        $this->logger->alert($msg, new Argument\Token\ArrayEntryToken('file', __FILE__))->shouldBeCalled();

        $request->withAttribute('error', $exception)->willReturn($request2);
        $this->errorController->execute($request2)->willReturn($response);

        $this->process($request, $requestHandler)->shouldReturn($response);
    }
}
