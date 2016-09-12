<?php declare(strict_types = 1);

namespace jschreuder\Middle\Controller;

final class ValidationFailedException extends \DomainException
{
    private $validationErrors;

    public function __construct(array $validationErrors)
    {
        parent::__construct('Validation failed', 400);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors() : array
    {
        return $this->validationErrors;
    }
}
