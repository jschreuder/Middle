<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\RouteMatch;
use PhpSpec\ObjectBehavior;

class RouteMatchSpec extends ObjectBehavior
{
    public function it_is_initializable(ControllerInterface $controller)
    {
        $attributes = ['answer' => 42];
        $this->beConstructedWith($controller, $attributes);
        $this->shouldHaveType(RouteMatch::class);
        $this->isMatch()->shouldReturn(true);
        $this->getController()->shouldReturn($controller);
        $this->getAttributes()->shouldReturn($attributes);
    }
}
