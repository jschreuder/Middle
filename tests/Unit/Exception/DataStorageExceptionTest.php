<?php

use jschreuder\Middle\Exception\DataStorageException;

test('it can be initialized', function () {
    $error = 'storage failed';
    $previous = new Exception();
    
    $exception = new DataStorageException($error, $previous);
    
    expect($exception)
        ->toBeInstanceOf(DataStorageException::class)
        ->and($exception->getMessage())->toBe($error)
        ->and($exception->getCode())->toBe(503)
        ->and($exception->getPrevious())->toBe($previous);
}); 