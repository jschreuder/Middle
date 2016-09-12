<?php

namespace spec\jschreuder\Middle\Controller;

use jschreuder\Middle\ApplicationInterface;
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
    /** @var  ApplicationInterface */
    private $application;

    /** @var  callable */
    private $errorHandler;

    public function let(ApplicationInterface $application)
    {
        $this->application = $application;
        $this->errorHandler = function () {};
        $this->beConstructedWith($application, $this->errorHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FilterValidationMiddleware::class);
    }

    public function it_can_filter_a_request(
        ServerRequestInterface $request1,
        ServerRequestInterface $request2,
        RequestFilterInterface $filter,
        ResponseInterface $response
    )
    {
        $request1->getAttribute('controller')->willReturn($filter);
        $filter->filterRequest($request1)->willReturn($request2);
        $this->application->execute($request2)->willReturn($response);
        $this->execute($request1)->shouldReturn($response);
    }

    public function it_can_successfully_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->shouldBeCalled();
        $this->application->execute($request)->willReturn($response);
        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_fail_to_validate_a_request(
        ServerRequestInterface $request,
        RequestValidatorInterface $validator,
        ResponseInterface $response
    )
    {
        $request->getAttribute('controller')->willReturn($validator);
        $validator->validateRequest($request)->willThrow(new ValidationFailedException([]));
        $this->application->execute($request)->shouldNotBeCalled();

        $this->beConstructedWith($this->application, function () use ($response) {
            return $response->getWrappedObject();
        });

        $this->execute($request)->shouldReturn($response);
    }

    public function it_can_be_neither(ServerRequestInterface $request, ResponseInterface $response)
    {
        $request->getAttribute('controller')->willReturn('trim');
        $this->application->execute($request)->willReturn($response);
        $this->execute($request)->shouldReturn($response);
    }
}
