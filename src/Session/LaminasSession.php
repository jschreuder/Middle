<?php declare(strict_types=1);

namespace jschreuder\Middle\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager;

final class LaminasSession implements SessionInterface
{
    const string FLASH_DATA_KEY_PREFIX = '_flash_data.';

    private bool $changed = false;

    public function __construct(
        private readonly SessionManager $sessionManager,
        private readonly Container $container
    )
    {
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->container[$key]);
    }

    #[\Override]
    public function get(string $key): mixed
    {
        return $this->container[$key];
    }

    #[\Override]
    public function set(string $key, $value): void
    {
        $this->changed = true;
        $this->container[$key] = $value;
    }

    #[\Override]
    public function hasFlash(string $key): bool
    {
        return isset($this->container[self::FLASH_DATA_KEY_PREFIX . $key]);
    }

    #[\Override]
    public function getFlash(string $key): mixed
    {
        return $this->container[self::FLASH_DATA_KEY_PREFIX . $key];
    }

    #[\Override]
    public function setFlash(string $key, $value): void
    {
        $key = self::FLASH_DATA_KEY_PREFIX . $key;
        $this->changed = true;
        $this->container[$key] = $value;
        $this->container->setExpirationHops(1, [$key]);
    }

    #[\Override]
    public function destroy(): void
    {
        $this->container->exchangeArray([]);
        $this->sessionManager->destroy();
    }

    #[\Override]
    public function rotateId(): void
    {
        $this->changed = true;
        $this->sessionManager->regenerateId();
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return empty($this->toArray());
    }

    #[\Override]
    public function hasChanged(): bool
    {
        return $this->changed;
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->container->getArrayCopy();
    }
}
