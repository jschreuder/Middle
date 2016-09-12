<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Zend\Session\Container;
use Zend\Session\SessionManager;

class ZendSession implements SessionInterface
{
    /** @var  SessionManager */
    private $sessionManager;

    /** @var  Container */
    private $container;

    private $changed = false;

    public function __construct(SessionManager $sessionManager, Container $container)
    {
        $this->sessionManager = $sessionManager;
        $this->container = $container;
    }

    public function has(string $key) : bool
    {
        return isset($this->container[$key]);
    }

    public function get(string $key)
    {
        return $this->container[$key];
    }

    /** @return  void */
    public function set(string $key, $value)
    {
        $this->changed = true;
        $this->container[$key] = $value;
    }

    public function getFlash(string $key)
    {
        return $this->container[$key];
    }

    /** @return  void */
    public function setFlash(string $key, $value)
    {
        $this->changed = true;
        $this->container[$key] = $value;
        $this->container->setExpirationHops(1, [$key]);
    }

    /** @return  void */
    public function destroy()
    {
        $this->sessionManager->destroy();
    }

    /** @return  void */
    public function rotateId()
    {
        $this->changed = true;
        $this->sessionManager->regenerateId();
    }

    public function isEmpty() : bool
    {
        return $this->container->count() > 0;
    }

    public function hasChanged() : bool
    {
        return $this->changed;
    }
}
