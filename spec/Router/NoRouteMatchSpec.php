<?php

namespace spec\jschreuder\Middle\Router;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Router\NoRouteMatch;
use PhpSpec\ObjectBehavior;

class NoRouteMatchSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NoRouteMatch::class);
        $this->isMatch()->shouldReturn(false);
    }

    public function it_does_not_have_a_controller()
    {
        $this->shouldThrow(\RuntimeException::class)->duringGetController();
    }

    public function it_does_not_have_attributes()
    {
        $this->shouldThrow(\RuntimeException::class)->duringGetAttributes();
    }
}
