<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

use jschreuder\Middle\Exception\ValidationFailedException;
use Psr\Http\Message\ServerRequestInterface;

interface RequestValidatorInterface
{
    /**
     * Validate the request's contents and throw ValidationFailedException
     * on failure.
     *
     * @return  void
     * @throws  ValidationFailedException
     */
    public function validateRequest(ServerRequestInterface $request): void;
}
