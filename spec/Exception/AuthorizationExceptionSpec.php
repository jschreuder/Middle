<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\AuthorizationException;
use PhpSpec\ObjectBehavior;

class AuthorizationExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $error = 'message',
            $previous = new \Exception()
        );

        $this->shouldHaveType(AuthorizationException::class);
        $this->getMessage()->shouldBe($error);
        $this->getCode()->shouldBe(403);
        $this->getPrevious()->shouldBe($previous);
    }
}
