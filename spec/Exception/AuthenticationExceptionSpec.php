<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\AuthenticationException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AuthenticationExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $error = 'message',
            $previous = new \Exception()
        );

        $this->shouldHaveType(AuthenticationException::class);
        $this->getMessage()->shouldBe($error);
        $this->getCode()->shouldBe(401);
        $this->getPrevious()->shouldBe($previous);
    }
}
