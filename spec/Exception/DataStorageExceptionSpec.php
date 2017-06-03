<?php

namespace spec\jschreuder\Middle\Exception;

use jschreuder\Middle\Exception\DataStorageException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DataStorageExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->beConstructedWith(
            $error = 'database error',
            $previous = new \Exception()
        );

        $this->shouldHaveType(DataStorageException::class);
        $this->getMessage()->shouldBe($error);
        $this->getCode()->shouldBe(503);
        $this->getPrevious()->shouldBe($previous);
    }
}
