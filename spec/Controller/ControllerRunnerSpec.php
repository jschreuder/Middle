<?php

namespace spec\jschreuder\Middle\Controller;

use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerRunner;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerRunnerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ControllerRunner::class);
    }

    public function it_can_execute_a_controller(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $controller = CallableController::fromCallable(function () use ($response) : ResponseInterface {
            return $response->getWrappedObject();
        });
        $request->getAttribute('controller')->willReturn($controller);
        $this->process($request, $requestHandler)->shouldReturn($response);
    }

    public function it_will_error_on_invalid_controller(
        ServerRequestInterface $request,
        RequestHandlerInterface $requestHandler
    )
    {
        $controller = function () {
            return 'an invalid response';
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->shouldThrow(\RuntimeException::class)->duringProcess($request, $requestHandler);
    }
}
