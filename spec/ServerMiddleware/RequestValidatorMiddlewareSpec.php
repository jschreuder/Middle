<?php

namespace spec\jschreuder\Middle\ServerMiddleware;

use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestValidatorMiddlewareSpec extends ObjectBehavior
{
    /** @var  callable */
    private $errorHandler;

    public function let()
    {
        $this->errorHandler = function () {
        };
        $this->beConstructedWith($this->errorHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RequestValidatorMiddleware::class);
    }

    public function it_can_successfully_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->shouldBeCalled();
        $requestHandler->handle($request)->willReturn($response);
        $this->process($request, $requestHandler)->shouldReturn($response);
    }

    public function it_can_fail_to_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->willThrow(new ValidationFailedException([]));
        $requestHandler->handle($request)->shouldNotBeCalled();

        $this->beConstructedWith(function () use ($response) {
            return $response->getWrappedObject();
        });

        $this->process($request, $requestHandler)->shouldReturn($response);
    }

    public function it_can_do_nothing(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RequestHandlerInterface $requestHandler
    )
    {
        $request->getAttribute('controller')->willReturn('trim');
        $requestHandler->handle($request)->willReturn($response);
        $this->process($request, $requestHandler)->shouldReturn($response);
    }
}
