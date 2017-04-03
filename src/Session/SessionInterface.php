<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

interface SessionInterface
{
    public function has(string $key): bool;

    /** @return  mixed */
    public function get(string $key);

    public function set(string $key, $value): void;

    public function hasFlash(string $key): bool;

    /** @return  mixed */
    public function getFlash(string $key);

    public function setFlash(string $key, $value): void;

    public function destroy(): void;

    public function rotateId(): void;

    public function isEmpty(): bool;

    public function hasChanged(): bool;

    public function toArray(): array;
}