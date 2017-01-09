<?php

namespace spec\jschreuder\Middle\Controller;

use Interop\Http\ServerMiddleware\DelegateInterface;
use jschreuder\Middle\Controller\FilterValidationMiddleware;
use jschreuder\Middle\Controller\RequestFilterInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Controller\ValidationFailedException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @mixin  FilterValidationMiddleware */
class FilterValidationMiddlewareSpec extends ObjectBehavior
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
        $this->shouldHaveType(FilterValidationMiddleware::class);
    }

    public function it_can_filter_a_request(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        RequestFilterInterface $filter,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request1->getAttribute('controller')->willReturn($filter);
        $filter->filterRequest($request1)->willReturn($request2);
        $delegate->process($request2)->willReturn($response);
        $this->process($request1, $delegate)->shouldReturn($response);
    }

    public function it_can_successfully_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->shouldBeCalled();
        $delegate->process($request)->willReturn($response);
        $this->process($request, $delegate)->shouldReturn($response);
    }

    public function it_can_fail_to_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->willThrow(new ValidationFailedException([]));
        $delegate->process($request)->shouldNotBeCalled();

        $this->beConstructedWith(function () use ($response) {
            return $response->getWrappedObject();
        });

        $this->process($request, $delegate)->shouldReturn($response);
    }

    public function it_can_be_neither(
        ServerRequestInterface $request,
        ResponseInterface $response,
        DelegateInterface $delegate
    )
    {
        $request->getAttribute('controller')->willReturn('trim');
        $delegate->process($request)->willReturn($response);
        $this->process($request, $delegate)->shouldReturn($response);
    }
}
