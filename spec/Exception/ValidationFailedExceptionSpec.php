<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\ValidationFailedException;
use PhpSpec\ObjectBehavior;

class ValidationFailedExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $errors = [
            'username' => 'you must have one',
            'password' => 'no, 12345678 is not a proper password',
        ];
        $this->beConstructedWith($errors);
        $this->shouldHaveType(ValidationFailedException::class);
        $this->getValidationErrors()->shouldReturn($errors);
    }
}
