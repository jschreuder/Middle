<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

final class RedirectView implements ViewInterface
{
    private int $statusCode;
    private array $headers = [];

    public function __construct(string $location, int $statusCode = 302)
    {
        if ($statusCode < 300 || $statusCode >= 400) {
            throw new \InvalidArgumentException('Redirect must have status code between 300-399');
        }

        $this->statusCode = $statusCode;
        $this->setHeader('Location', $location);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContentType(): string
    {
        throw new \RuntimeException('No content-type allowed on RedirectView');
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function getTemplate(): string
    {
        throw new \RuntimeException('No template allowed on RedirectView');
    }

    public function getParameters(): array
    {
        return [];
    }

    public function setParameter(string $key, $value): void
    {
        throw new \RuntimeException('No parameters allowed on RedirectView');
    }
}
