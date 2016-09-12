<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Zend\Session\Container;
use Zend\Session\SessionManager;

final class ZendSession implements SessionInterface
{
    const FLASH_DATA_KEY_PREFIX = '_flash_data.';

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

    public function hasFlash(string $key) : bool
    {
        return isset($this->container[self::FLASH_DATA_KEY_PREFIX . $key]);
    }

    public function getFlash(string $key)
    {
        return $this->container[self::FLASH_DATA_KEY_PREFIX . $key];
    }

    /** @return  void */
    public function setFlash(string $key, $value)
    {
        $key = self::FLASH_DATA_KEY_PREFIX . $key;
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
        return $this->container->count() === 0;
    }

    public function hasChanged() : bool
    {
        return $this->changed;
    }

    public function toArray() : array
    {
        return $this->container->getArrayCopy();
    }
}
