<?php

use jschreuder\Middle\View\RedirectView;

test('it can be initialized', function () {
    $view = new RedirectView('/path');
    
    expect($view)
        ->toBeInstanceOf(RedirectView::class)
        ->and($view->getHeaders())->toBe(['Location' => '/path'])
        ->and($view->getStatusCode())->toBe(302)
        ->and($view->getParameters())->toBe([]);
    
    expect(fn() => $view->getContentType())->toThrow(RuntimeException::class);
    expect(fn() => $view->getTemplate())->toThrow(RuntimeException::class);
    expect(fn() => $view->setParameter('another', 'not-allowed'))->toThrow(RuntimeException::class);
});

test('it errors on non redirect status', function () {
    expect(fn() => new RedirectView('/nope', 400))->toThrow(InvalidArgumentException::class);
}); 