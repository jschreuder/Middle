<?php declare(strict_types = 1);

namespace jschreuder\Middle\Session;

final class Session implements SessionInterface
{
    const FLASH_DATA_META_KEY = '_flash_data_keys';
    const FLASH_DATA_KEY_PREFIX = '_flash_data.';

    /** @var  array */
    private $sessionData;

    /** @var  bool */
    private $changed = false;

    public function __construct(array $sessionData = [])
    {
        $this->sessionData = $this->processFlashData($sessionData);
    }

    private function processFlashData(array $sessionData): array
    {
        if (!isset($sessionData[self::FLASH_DATA_META_KEY])) {
            return $sessionData;
        }

        foreach ($sessionData[self::FLASH_DATA_META_KEY] as $key => $hops) {
            if ($hops <= 0) {
                unset($sessionData[self::FLASH_DATA_META_KEY][$key]);
                unset($sessionData[self::FLASH_DATA_KEY_PREFIX . $key]);
                $this->changed = true;
            } else {
                $sessionData[self::FLASH_DATA_META_KEY][$key] = $hops - 1;
                $this->changed = true;
            }
        }
        return $sessionData;
    }

    public function has(string $key): bool
    {
        return isset($this->sessionData[$key]);
    }

    public function get(string $key)
    {
        return $this->sessionData[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $this->changed = true;
        $this->sessionData[$key] = $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($this->sessionData[self::FLASH_DATA_KEY_PREFIX . $key]);
    }

    public function getFlash(string $key)
    {
        return $this->sessionData[self::FLASH_DATA_KEY_PREFIX . $key] ?? null;
    }

    public function setFlash(string $key, $value): void
    {
        $this->changed = true;
        $this->markFlashKey($key);
        $this->sessionData[self::FLASH_DATA_KEY_PREFIX . $key] = $value;
    }

    /** @return  void */
    private function markFlashKey(string $key)
    {
        $this->sessionData[self::FLASH_DATA_META_KEY][$key] = 1;
    }

    public function destroy(): void
    {
        $this->changed = true;
        $this->sessionData = [];
    }

    public function rotateId(): void
    {
        $this->changed = true;
        // These sessions don't have an ID to change, but this should force overwrite
    }

    public function isEmpty(): bool
    {
        return count($this->sessionData) === 0;
    }

    public function hasChanged(): bool
    {
        return $this->changed;
    }

    public function toArray(): array
    {
        return $this->sessionData;
    }
}
