<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

class GenericSession implements SessionInterface
{
    /** @var  array */
    private $sessionData;

    /** @var  bool */
    private $changed = false;

    public function __construct(array $sessionData = [])
    {
        $this->sessionData = $sessionData;
    }

    public function has(string $key) : bool
    {
        return isset($this->sessionData[$key]);
    }

    public function get(string $key)
    {
        return $this->sessionData[$key] ?? null;
    }

    public function set(string $key, $value)
    {
        $this->changed = true;
        $this->sessionData[$key] = $value;
    }

    public function getFlash(string $key)
    {
        return $this->sessionData[$key] ?? null;
    }

    public function setFlash(string $key, $value)
    {
        $this->changed = true;
        $this->sessionData[$key] = $value;
    }

    public function destroy()
    {
        $this->sessionData = [];
    }

    public function rotateId()
    {
        // These sessions don't have an ID
    }

    public function isEmpty() : bool
    {
        return count($this->sessionData) > 0;
    }

    public function hasChanged() : bool
    {
        return $this->changed;
    }
}
