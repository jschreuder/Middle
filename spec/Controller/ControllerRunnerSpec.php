<?php

namespace spec\jschreuder\Middle\Controller;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerRunner;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  ControllerRunner */
class ControllerRunnerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ControllerRunner::class);
    }

    public function it_can_execute_a_controller(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $controller = CallableController::fromCallable(function () use ($response) : ResponseInterface {
            return $response->getWrappedObject();
        });
        $request->getAttribute('controller')->willReturn($controller);
        $this->process($request, $delegate)->shouldReturn($response);
    }

    public function it_will_error_on_invalid_controller(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $controller = function () {
            return 'an invalid response';
        };
        $request->getAttribute('controller')->willReturn($controller);
        $this->shouldThrow(\RuntimeException::class)->duringProcess($request, $delegate);
    }
}
