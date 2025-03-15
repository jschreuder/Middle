<?php

use jschreuder\Middle\Exception\AuthorizationException;

test('it can be initialized', function () {
    $error = 'authorization failed';
    $previous = new Exception();
    
    $exception = new AuthorizationException($error, $previous);
    
    expect($exception)
        ->toBeInstanceOf(AuthorizationException::class)
        ->and($exception->getMessage())->toBe($error)
        ->and($exception->getCode())->toBe(403)
        ->and($exception->getPrevious())->toBe($previous);
}); 