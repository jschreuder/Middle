<?php

use jschreuder\Middle\Exception\AuthenticationException;

test("it can be initialized", function () {
    $error = "authentication failed";
    $previous = new Exception();

    $exception = new AuthenticationException($error, $previous);

    expect($exception)
        ->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getMessage())
        ->toBe($error)
        ->and($exception->getCode())
        ->toBe(401)
        ->and($exception->getPrevious())
        ->toBe($previous);
});
