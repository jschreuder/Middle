<?php declare(strict_types = 1);

namespace jschreuder\Middle\Application\View;

class View implements ViewInterface
{
    /** @var  int */
    private $statusCode;

    /** @var  array */
    private $headers;

    /** @var  string */
    private $contentType;

    /** @var  string */
    private $template;

    /** @var  array */
    private $parameters;

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

    public function getStatusCode() : int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @return  void */
    public function setHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;
    }

    public function getContentType() : string
    {
        return $this->contentType;
    }

    public function getTemplate() : string
    {
        return $this->template;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    /** @return  void */
    public function setParameter(string $key, $value)
    {
        $this->parameters[$key] = $value;
    }
}
