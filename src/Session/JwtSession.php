<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

use PSR7Session\Session\SessionInterface as JwtSessionInterface;

class JwtSession implements SessionInterface
{
    /** @var  JwtSessionInterface */
    private $jwtSession;

    public function __construct(JwtSessionInterface $jwtSession)
    {
        $this->jwtSession = $jwtSession;
    }

    public function has(string $key) : bool
    {
        return $this->jwtSession->has($key);
    }

    public function get(string $key)
    {
        return $this->jwtSession->get($key);
    }

    public function set(string $key, $value)
    {
        $this->jwtSession->set($key, $value);
    }

    public function getFlash(string $key)
    {
        return $this->jwtSession->get($key);
    }

    public function setFlash(string $key, $value)
    {
        $this->jwtSession->set($key, $value);
    }

    public function destroy()
    {
        $this->jwtSession->clear();
    }

    public function rotateId()
    {
        // Does nothing with JWT based session, they don't have an ID
    }
}
