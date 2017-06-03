<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\InputException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class InputExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $error = 'bad input',
            $previous = new \Exception()
        );

        $this->shouldHaveType(InputException::class);
        $this->getMessage()->shouldBe($error);
        $this->getCode()->shouldBe(400);
        $this->getPrevious()->shouldBe($previous);
    }
}
