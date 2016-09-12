<?php

namespace spec\jschreuder\Middle;

use jschreuder\Middle\ApplicationStack;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/** @mixin  ApplicationStack */
class ApplicationStackSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ApplicationStack::class);
    }
}
