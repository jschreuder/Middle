<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\Session;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/** @mixin  Session */
class SessionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Session::class);
    }

    public function it_can_process_vars_and_get_at_them()
    {
        $session = ['test' => 'data'];
        $this->beConstructedWith($session);

        $this->hasChanged()->shouldBe(false);
        $this->has('test')->shouldBe(true);
        $this->hasFlash('test')->shouldBe(false);
    }

    public function it_can_process_flash_vars_and_get_at_them()
    {
        $session = [
            Session::FLASH_DATA_META_KEY => [
                'test' => 1,
                'nope' => 0,
            ],
            Session::FLASH_DATA_KEY_PREFIX . 'test' => 'data',
            Session::FLASH_DATA_KEY_PREFIX . 'nope' => 'will-disappear',
        ];
        $this->beConstructedWith($session);

        $this->hasChanged()->shouldBe(true); // for getting rid of expired flash var
        $this->has('test')->shouldBe(false);
        $this->hasFlash('test')->shouldBe(true);
        $this->has('nope')->shouldBe(false);
        $this->hasFlash('nope')->shouldBe(false);

        // Check if test will expire next request
        $array = $this->toArray();
        $array[Session::FLASH_DATA_META_KEY]['test']->shouldBe(0);
    }

    public function it_can_overwrite_vars()
    {
        $session = ['test' => 'data'];
        $this->beConstructedWith($session);

        $this->get('test')->shouldBe($session['test']);
        $this->hasChanged()->shouldBe(false);

        $this->set('test', 'nest');
        $this->get('test')->shouldBe('nest');
        $this->hasChanged()->shouldBe(true);
    }

    public function it_can_set_flash_vars()
    {
        $this->hasFlash('test')->shouldBe(false);
        $this->setFlash('test', 'data');
        $this->hasFlash('test')->shouldBe(true);

        $array = $this->toArray();
        $array[Session::FLASH_DATA_META_KEY]['test']->shouldBe(1);
    }

    public function it_can_destroy_a_session()
    {
        $this->beConstructedWith(['test' => 'data']);
        $this->isEmpty()->shouldBe(false);
        $this->hasChanged()->shouldBe(false);

        $this->destroy();
        $this->isEmpty()->shouldBe(true);
        $this->hasChanged()->shouldBe(true);
    }

    public function it_should_be_changed_instead_of_rotating_a_non_id()
    {
        $this->hasChanged()->shouldBe(false);
        $this->rotateId();
        $this->hasChanged()->shouldBe(true);
    }
}
