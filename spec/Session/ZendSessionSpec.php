<?php

namespace spec\jschreuder\Middle\Session;

use jschreuder\Middle\Session\ZendSession;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Zend\Session\Container;
use Zend\Session\SessionManager;

/** @mixin  ZendSession */
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
        $this->container->offsetSet('test', 'something')->shouldBeCalled();
        $this->set('test', 'something');
    }

    public function it_can_get()
    {
        $this->container = new \ArrayObject();
        $this->container->offsetSet('test', 'something');

        // Prophecy balks at returning by reference, so we'll just hack another container in there
        $uglyWorkaround = new \ReflectionProperty($this->getWrappedObject(), 'container');
        $uglyWorkaround->setAccessible(true);
        $uglyWorkaround->setValue($this->getWrappedObject(), $this->container);

        $this->get('test')->shouldBe('something');
    }

    public function it_can_get_flash_vars()
    {
        $this->container = new \ArrayObject();
        $this->container->offsetSet('test', 'something');

        // Prophecy balks at returning by reference, so we'll just hack another container in there
        $uglyWorkaround = new \ReflectionProperty($this->getWrappedObject(), 'container');
        $uglyWorkaround->setAccessible(true);
        $uglyWorkaround->setValue($this->getWrappedObject(), $this->container);

        $this->getFlash('test')->shouldBe('something');
    }

    public function it_can_set_flash_vars()
    {
        $this->container->offsetSet('test', 'something')->shouldBeCalled();
        $this->container->setExpirationHops(1, ['test'])->shouldBeCalled();
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
}
