<?php declare(strict_types=1);

namespace jschreuder\Middle\Session;

interface SessionInterface
{
    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function set(string $key, $value): void;

    public function hasFlash(string $key): bool;

    public function getFlash(string $key): mixed;

    public function setFlash(string $key, $value): void;

    public function destroy(): void;

    public function rotateId(): void;

    public function isEmpty(): bool;

    public function hasChanged(): bool;

    public function toArray(): array;
}
