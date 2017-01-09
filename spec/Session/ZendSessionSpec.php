<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\ZendSession;
use PhpSpec\ObjectBehavior;
use Zend\Session\Container;
use Zend\Session\SessionManager;

class ZendSessionSpec extends ObjectBehavior
{
    /** @var  SessionManager */
    private $sessionManager;

    /** @var  Container */
    private $container;

    public function let(SessionManager $sessionManager, Container $container)
    {
        $this->sessionManager = $sessionManager;
        $this->container = $container;
        $this->beConstructedWith($sessionManager, $container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ZendSession::class);
    }

    public function it_can_check()
    {
        $this->container->offsetExists('test')->willReturn(true);
        $this->has('test')->shouldBe(true);
    }

    public function it_can_set()
    {
        $this->hasChanged()->shouldBe(false);
        $this->container->offsetSet('test', 'something')->shouldBeCalled();
        $this->set('test', 'something');
        $this->hasChanged()->shouldBe(true);
    }

    public function it_can_get()
    {
        $this->container = new \ArrayObject();
        $this->container->offsetSet('test', 'something');

        // Prophecy balks at returning by reference, so we'll just hack another container in there
        $uglyWorkaround = new \ReflectionProperty($this->getWrappedObject(), 'container');
        $uglyWorkaround->setAccessible(true);
        $uglyWorkaround->setValue($this->getWrappedObject(), $this->container);

        $this->hasFlash('test')->shouldBe(false);
        $this->get('test')->shouldBe('something');
    }

    public function it_can_get_flash_vars()
    {
        $this->container = new \ArrayObject();
        $this->container->offsetSet(ZendSession::FLASH_DATA_KEY_PREFIX . 'test', 'something');

        // Prophecy balks at returning by reference, so we'll just hack another container in there
        $uglyWorkaround = new \ReflectionProperty($this->getWrappedObject(), 'container');
        $uglyWorkaround->setAccessible(true);
        $uglyWorkaround->setValue($this->getWrappedObject(), $this->container);

        $this->has('test')->shouldBe(false);
        $this->hasFlash('test')->shouldBe(true);
        $this->getFlash('test')->shouldBe('something');
    }

    public function it_can_set_flash_vars()
    {
        $this->container->offsetSet(ZendSession::FLASH_DATA_KEY_PREFIX . 'test', 'something')->shouldBeCalled();
        $this->container->setExpirationHops(1, [ZendSession::FLASH_DATA_KEY_PREFIX . 'test'])->shouldBeCalled();
        $this->setFlash('test', 'something');
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
        $this->container->count()->willReturn(0);
        $this->isEmpty()->shouldBe(true);
    }

    public function it_can_check_if_non_empty()
    {
        $this->container->count()->willReturn(1);
        $this->isEmpty()->shouldBe(false);
    }

    public function it_can_get_array_representation_of_session()
    {
        $array = ['test' => 'data'];
        $this->container->getArrayCopy()->willReturn($array);
        $this->toArray()->shouldBe($array);
    }
}
