<?php declare(strict_types = 1);

namespace jschreuder\Middle\Router;

interface UrlGeneratorInterface
{
    /** Generate a relative path */
    public function generatePath(string $name, array $options = []) : string;

    /** Generate a full URL */
    public function generateUrl(string $name, array $options = []) : string;
}