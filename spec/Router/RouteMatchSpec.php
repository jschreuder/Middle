<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;
use PhpSpec\ObjectBehavior;

class RouteMatchSpec extends ObjectBehavior
{
    public function it_is_initializable_as_no_match()
    {
        $this->beConstructedWith(false);
        $this->shouldHaveType(RouteMatch::class);
        $this->isMatch()->shouldReturn(false);
    }

    public function it_is_initializable_as_match(ControllerInterface $controller)
    {
        $attributes = ['answer' => 42];
        $this->beConstructedWith(true, $controller, $attributes);
        $this->shouldHaveType(RouteMatch::class);
        $this->isMatch()->shouldReturn(true);
        $this->getController()->shouldReturn($controller);
        $this->getAttributes()->shouldReturn($attributes);
    }

    public function it_cannot_be_a_match_without_controller()
    {
        $this->beConstructedWith(true);
        $this->shouldThrow(\RuntimeException::class)->duringInstantiation();
    }
}
