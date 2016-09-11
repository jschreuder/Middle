<?php declare(strict_types = 1);

namespace jschreuder\Middle\View;

interface ViewInterface
{
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';

    public function getStatusCode() : int;

    public function getContentType() : string;

    public function getHeaders() : array;

    /** @return  void */
    public function setHeader(string $key, string $value);

    public function getTemplate() : string;

    public function getParameters() : array;

    /** @return  void */
    public function setParameter(string $key, $value);
}