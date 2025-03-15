<?php

use jschreuder\Middle\Session\Session;

test('it can be initialized', function () {
    $session = new Session([]);
    expect($session)->toBeInstanceOf(Session::class);
});

test('it can process vars and get at them', function () {
    $sessionData = ['test' => 'data'];
    $session = new Session($sessionData);
    
    expect($session->hasChanged())->toBeFalse();
    expect($session->has('test'))->toBeTrue();
    expect($session->hasFlash('test'))->toBeFalse();
});

test('it can process flash vars and get at them', function () {
    $sessionData = [
        Session::FLASH_DATA_META_KEY => [
            'test' => 1,
            'nope' => 0,
        ],
        Session::FLASH_DATA_KEY_PREFIX . 'test' => 'data',
        Session::FLASH_DATA_KEY_PREFIX . 'nope' => 'will-disappear',
    ];
    $session = new Session($sessionData);
    
    expect($session->hasChanged())->toBeTrue(); // for getting rid of expired flash var
    expect($session->has('test'))->toBeFalse();
    expect($session->hasFlash('test'))->toBeTrue();
    expect($session->has('nope'))->toBeFalse();
    expect($session->hasFlash('nope'))->toBeFalse();
    
    // Check if test will expire next request
    $array = $session->toArray();
    expect($array[Session::FLASH_DATA_META_KEY]['test'])->toBe(0);
});

test('it can overwrite vars', function () {
    $sessionData = ['test' => 'data'];
    $session = new Session($sessionData);
    
    expect($session->get('test'))->toBe($sessionData['test']);
    expect($session->hasChanged())->toBeFalse();
    
    $session->set('test', 'nest');
    expect($session->get('test'))->toBe('nest');
    expect($session->hasChanged())->toBeTrue();
});

test('it can set flash vars', function () {
    $session = new Session([]);
    
    expect($session->hasFlash('test'))->toBeFalse();
    $session->setFlash('test', 'data');
    expect($session->hasFlash('test'))->toBeTrue();
    expect($session->getFlash('test'))->toBe('data');
    
    $array = $session->toArray();
    expect($array[Session::FLASH_DATA_META_KEY]['test'])->toBe(1);
});

test('it can destroy a session', function () {
    $session = new Session(['test' => 'data']);
    expect($session->isEmpty())->toBeFalse();
    expect($session->hasChanged())->toBeFalse();
    
    $session->destroy();
    expect($session->isEmpty())->toBeTrue();
    expect($session->hasChanged())->toBeTrue();
});

test('it should be changed instead of rotating a non id', function () {
    $session = new Session([]);
    expect($session->hasChanged())->toBeFalse();
    $session->rotateId();
    expect($session->hasChanged())->toBeTrue();
}); 