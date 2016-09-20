<?php

namespace spec\jschreuder\Middle\Controller;

use jschreuder\Middle\Controller\CallableController;
use jschreuder\Middle\Controller\ControllerInterface;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  CallableController */
class CallableControllerSpec extends ObjectBehavior
{
    private $response;

    public function let(ResponseInterface $response)
    {
        $this->response = $response;
        $this->beConstructedThrough('fromCallable', [function () {
            return $this->response instanceof Collaborator ? $this->response->getWrappedObject() : $this->response;
        }]);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CallableController::class);
        $this->shouldHaveType(ControllerInterface::class);
    }

    public function it_can_execute(ServerRequestInterface $request)
    {
        $this->execute($request)->shouldReturn($this->response);
    }

    public function it_errors_on_non_response(ServerRequestInterface $request)
    {
        $this->response = 'nope';
        $this->shouldThrow(\TypeError::class)->duringExecute($request);
    }
}
