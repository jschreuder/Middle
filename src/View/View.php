<?php declare(strict_types = 1);

namespace jschreuder\Middle\View;

final class View implements ViewInterface
{
    private int $statusCode;
    private array $headers;
    private string $contentType;
    private string $template;
    private array $parameters;

    public function __construct(
        string $template,
        array $parameters = [],
        int $statusCode = 200,
        string $contentType = self::CONTENT_TYPE_HTML,
        array $headers = []
    )
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->contentType = $contentType;
        $this->template = $template;
        $this->parameters = $parameters;
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
