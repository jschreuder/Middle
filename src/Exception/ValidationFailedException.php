<?php declare(strict_types=1);

namespace jschreuder\Middle\Exception;

final class ValidationFailedException extends InputException
{
    public function __construct(
        private readonly array $validationErrors
    )
    {
        parent::__construct('Validation failed');
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
