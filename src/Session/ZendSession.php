<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use Zend\Session\Container;
use Zend\Session\SessionManager;

class ZendSession implements SessionInterface
{
    /** @var  SessionManager */
    private $sessionManager;

    /** @var  Container */
    private $values;

    public function __construct(SessionManager $sessionManager, string $namespace)
    {
        $this->sessionManager = $sessionManager;
        $this->values = new Container(str_replace('.', '_', $namespace), $sessionManager);
    }

    public function has(string $key) : bool
    {
        return isset($this->values[$key]);
    }

    public function get(string $key)
    {
        return $this->values[$key];
    }

    /** @return  void */
    public function set(string $key, $value)
    {
        $this->values[$key] = $value;
    }

    public function getFlash(string $key)
    {
        return $this->values[$key];
    }

    /** @return  void */
    public function setFlash(string $key, $value)
    {
        $this->values[$key] = $value;
        $this->values->setExpirationHops(1, [$key]);
    }

    /** @return  void */
    public function destroy()
    {
        $this->sessionManager->destroy();
    }

    /** @return  void */
    public function rotateId()
    {
        $this->sessionManager->regenerateId();
    }
}
