<?php

use jschreuder\Middle\Exception\ValidationFailedException;

test('it can be initialized', function () {
    $errors = ['field' => 'validation failed'];
    
    $exception = new ValidationFailedException($errors);
    
    expect($exception)
        ->toBeInstanceOf(ValidationFailedException::class)
        ->and($exception->getMessage())->toBe('Validation failed')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getValidationErrors())->toBe($errors);
}); 