<?php declare(strict_types = 1);

namespace jschreuder\Middle\Exception;

use Throwable;

class AuthenticationException extends \Exception
{
    public function __construct($message = '', Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
