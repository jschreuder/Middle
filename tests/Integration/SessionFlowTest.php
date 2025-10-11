<?php

use jschreuder\Middle\Session\LaminasSession;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

beforeEach(function () {
    // Clear session data before each test
    $_SESSION = [];
});

afterEach(function () {
    // Clean up after each test
    $_SESSION = [];
});

test('flash data persists for exactly one additional request', function () {
    // First request - set flash data
    $sessionManager1 = new SessionManager();
    $container1 = new Container('test', $sessionManager1);
    $session1 = new LaminasSession($sessionManager1, $container1);
    
    $session1->setFlash('message', 'Hello');
    expect($session1->hasFlash('message'))->toBeTrue();
    expect($session1->getFlash('message'))->toBe('Hello');
    
    // Simulate second request - flash should still be there
    // LaminasSession decrements hop count on each request
    $sessionManager2 = new SessionManager();
    $container2 = new Container('test', $sessionManager2);
    $session2 = new LaminasSession($sessionManager2, $container2);
    
    expect($session2->hasFlash('message'))->toBeTrue();
    expect($session2->getFlash('message'))->toBe('Hello');
    
    // Force expiration processing by creating another session instance
    $sessionManager3 = new SessionManager();
    $container3 = new Container('test', $sessionManager3);
    $session3 = new LaminasSession($sessionManager3, $container3);
    
    // After hop expiration, flash should be gone
    expect($session3->hasFlash('message'))->toBeFalse();
});

test('regular session data persists across requests', function () {
    // First request - set regular data
    $sessionManager1 = new SessionManager();
    $container1 = new Container('test', $sessionManager1);
    $session1 = new LaminasSession($sessionManager1, $container1);
    
    $session1->set('user_id', 123);
    expect($session1->has('user_id'))->toBeTrue();
    expect($session1->get('user_id'))->toBe(123);
    
    // Second request - data should persist
    $sessionManager2 = new SessionManager();
    $container2 = new Container('test', $sessionManager2);
    $session2 = new LaminasSession($sessionManager2, $container2);
    
    expect($session2->has('user_id'))->toBeTrue();
    expect($session2->get('user_id'))->toBe(123);
    
    // Third request - data should still persist
    $sessionManager3 = new SessionManager();
    $container3 = new Container('test', $sessionManager3);
    $session3 = new LaminasSession($sessionManager3, $container3);
    
    expect($session3->has('user_id'))->toBeTrue();
    expect($session3->get('user_id'))->toBe(123);
});

test('session can be destroyed', function () {
    $sessionManager = new SessionManager();
    $container = new Container('test', $sessionManager);
    $session = new LaminasSession($sessionManager, $container);
    
    $session->set('user_id', 123);
    expect($session->isEmpty())->toBeFalse();
    
    $session->destroy();
    expect($session->isEmpty())->toBeTrue();
});

test('session tracks changes', function () {
    $sessionManager = new SessionManager();
    $container = new Container('test', $sessionManager);
    $session = new LaminasSession($sessionManager, $container);
    
    expect($session->hasChanged())->toBeFalse();
    
    $session->set('key', 'value');
    expect($session->hasChanged())->toBeTrue();
});

test('flash and regular data coexist', function () {
    $sessionManager = new SessionManager();
    $container = new Container('test', $sessionManager);
    $session = new LaminasSession($sessionManager, $container);
    
    $session->set('user_id', 123);
    $session->setFlash('message', 'Welcome');
    
    expect($session->has('user_id'))->toBeTrue();
    expect($session->hasFlash('message'))->toBeTrue();
    
    // Regular data is not confused with flash data
    expect($session->has('message'))->toBeFalse();
    expect($session->hasFlash('user_id'))->toBeFalse();
});