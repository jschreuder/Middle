<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

interface SessionInterface
{
    public function has(string $key): bool;

    /** @return  mixed */
    public function get(string $key);

    /** @return  void */
    public function set(string $key, $value);

    public function hasFlash(string $key): bool;

    /** @return  mixed */
    public function getFlash(string $key);

    /** @return  void */
    public function setFlash(string $key, $value);

    /** @return  void */
    public function destroy();

    /** @return  void */
    public function rotateId();

    public function isEmpty(): bool;

    public function hasChanged(): bool;

    public function toArray(): array;
}