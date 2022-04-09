<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\ZendSession;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use PhpSpec\ObjectBehavior;

class ZendSessionSpec extends ObjectBehavior
{
    /** @var  SessionManager */
    private $sessionManager;

    /** @var  Container */
    private $container;

    public function let(SessionManager $sessionManager)
    {
        $_SESSION = []; // fugly fix to empty session each time to test with Container
        $this->container = new Container();

        $this->sessionManager = $sessionManager;
        $this->beConstructedWith($sessionManager, $this->container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ZendSession::class);
    }

    public function it_can_check()
    {
        $this->container['test'] = 'exists';
        $this->has('test')->shouldBe(true);
    }

    public function it_can_get()
    {
        $this->container['test'] = 'something';
        $this->hasFlash('test')->shouldBe(false);
        $this->get('test')->shouldBe('something');
    }

    public function it_can_set()
    {
        $this->hasChanged()->shouldBe(false);
        $this->set('test', 'something');
        $this->get('test')->shouldReturn('something');
        $this->hasChanged()->shouldBe(true);
    }

    public function it_can_get_flash_vars()
    {
        $this->container[ZendSession::FLASH_DATA_KEY_PREFIX . 'test'] = 'something';
        $this->has('test')->shouldBe(false);
        $this->hasFlash('test')->shouldBe(true);
        $this->getFlash('test')->shouldBe('something');
    }

    public function it_can_set_flash_vars()
    {
        $this->setFlash('test', 'something');
        $this->getFlash('test')->shouldReturn('something');
    }

    public function it_can_end_a_session()
    {
        $this->sessionManager->destroy()->shouldBeCalled();
        $this->destroy();
    }

    public function it_can_rotate_a_session()
    {
        $this->sessionManager->regenerateId()->shouldBeCalled();
        $this->rotateId();
    }

    public function it_can_check_if_empty()
    {
        $this->isEmpty()->shouldBe(true);
    }

    public function it_can_check_if_non_empty()
    {
        $this->set('test', 'something');
        $this->isEmpty()->shouldBe(false);
    }

    public function it_can_get_array_representation_of_session()
    {
        $array = ['test' => 'data'];
        $this->container->exchangeArray($array);
        $this->toArray()->shouldBe($array);
    }
}
