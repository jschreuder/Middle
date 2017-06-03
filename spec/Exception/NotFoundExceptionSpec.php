<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\NotFoundException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotFoundExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $error = 'not found',
            $previous = new \Exception()
        );

        $this->shouldHaveType(NotFoundException::class);
        $this->getMessage()->shouldBe($error);
        $this->getCode()->shouldBe(404);
        $this->getPrevious()->shouldBe($previous);
    }
}
