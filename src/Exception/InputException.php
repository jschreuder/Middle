<?php declare(strict_types = 1);

namespace jschreuder\Middle\Exception;

use Throwable;

class InputException extends \Exception
{
    public function __construct($message = '', Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
