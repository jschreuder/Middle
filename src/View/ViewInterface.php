<?php declare(strict_types=1);

namespace jschreuder\Middle\View;

interface ViewInterface
{
    const string CONTENT_TYPE_HTML = 'text/html';
    const string CONTENT_TYPE_JSON = 'application/json';

    public function getStatusCode(): int;

    public function getContentType(): string;

    public function getHeaders(): array;

    public function setHeader(string $key, string $value): void;

    public function getTemplate(): string;

    public function getParameters(): array;

    public function setParameter(string $key, $value): void;
}