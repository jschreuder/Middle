<?php declare(strict_types=1);

namespace jschreuder\Middle\Exception;

use RuntimeException;
use Throwable;

class ApplicationStackException extends RuntimeException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
