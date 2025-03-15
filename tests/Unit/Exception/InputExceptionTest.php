<?php

use jschreuder\Middle\Exception\InputException;

test('it can be initialized', function () {
    $error = 'bad input';
    $previous = new Exception();
    
    $exception = new InputException($error, $previous);
    
    expect($exception)
        ->toBeInstanceOf(InputException::class)
        ->and($exception->getMessage())->toBe($error)
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getPrevious())->toBe($previous);
}); 