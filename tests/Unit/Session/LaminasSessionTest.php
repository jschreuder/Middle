<?php

use jschreuder\Middle\Session\LaminasSession;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

beforeEach(function () {
    $_SESSION = []; // Reset session for each test
    $this->container = new Container();
    $this->sessionManager = Mockery::mock(SessionManager::class);
    $this->session = new LaminasSession($this->sessionManager, $this->container);
});

test('it can be initialized', function () {
    expect($this->session)->toBeInstanceOf(LaminasSession::class);
});

test('it can check', function () {
    $this->container['test'] = 'exists';
    expect($this->session->has('test'))->toBeTrue();
});

test('it can get', function () {
    $this->container['test'] = 'something';
    expect($this->session->hasFlash('test'))->toBeFalse();
    expect($this->session->get('test'))->toBe('something');
});

test('it can set', function () {
    expect($this->session->hasChanged())->toBeFalse();
    $this->session->set('test', 'something');
    expect($this->session->get('test'))->toBe('something');
    expect($this->session->hasChanged())->toBeTrue();
});

test('it can get flash vars', function () {
    $this->container[LaminasSession::FLASH_DATA_KEY_PREFIX . 'test'] = 'something';
    expect($this->session->has('test'))->toBeFalse();
    expect($this->session->hasFlash('test'))->toBeTrue();
    expect($this->session->getFlash('test'))->toBe('something');
});

test('it can set flash vars', function () {
    $this->session->setFlash('test', 'something');
    expect($this->session->getFlash('test'))->toBe('something');
});

test('it can end a session', function () {
    $this->sessionManager->shouldReceive('destroy')->once();
    $this->session->destroy();
});

test('it can rotate a session', function () {
    $this->sessionManager->shouldReceive('regenerateId')->once();
    $this->session->rotateId();
});

test('it can check if empty', function () {
    expect($this->session->isEmpty())->toBeTrue();
});

test('it can check if non empty', function () {
    $this->session->set('test', 'something');
    expect($this->session->isEmpty())->toBeFalse();
});

test('it can get array representation of session', function () {
    $array = ['test' => 'data'];
    $this->container->exchangeArray($array);
    expect($this->session->toArray())->toBe($array);
}); 