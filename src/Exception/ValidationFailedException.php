<?php declare(strict_types=1);

namespace jschreuder\Middle\Exception;

final class ValidationFailedException extends InputException
{
    private array $validationErrors;

    public function __construct(array $validationErrors)
    {
        parent::__construct('Validation failed');
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
