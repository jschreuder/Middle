<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

final class View implements ViewInterface
{
    public function __construct(
        private readonly string $template,
        private array $parameters = [],
        private readonly int $statusCode = 200,
        private readonly string $contentType = self::CONTENT_TYPE_HTML,
        private array $headers = []
    )
    {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }
}
