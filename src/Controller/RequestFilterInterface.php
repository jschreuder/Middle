<?php declare(strict_types=1);

namespace jschreuder\Middle\Controller;

use Psr\Http\Message\ServerRequestInterface;

interface RequestFilterInterface
{
    /** Filters the request's contents and returns the filtered result */
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface;
}
