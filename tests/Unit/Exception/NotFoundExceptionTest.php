<?php

use jschreuder\Middle\Exception\NotFoundException;

test("it can be initialized", function () {
    $error = "not found";
    $previous = new Exception();

    $exception = new NotFoundException($error, $previous);

    expect($exception)
        ->toBeInstanceOf(NotFoundException::class)
        ->and($exception->getMessage())
        ->toBe($error)
        ->and($exception->getCode())
        ->toBe(404)
        ->and($exception->getPrevious())
        ->toBe($previous);
});
