<?php

use jschreuder\Middle\Session\Session;

describe('Session flash data and normal persistance integration test', function() {
    test('flash data persists for exactly one additional request', function () {
        // First request - set flash data
        $session1 = new Session([]);
        $session1->setFlash('message', 'Hello');
        expect($session1->hasFlash('message'))->toBeTrue();
        expect($session1->getFlash('message'))->toBe('Hello');
        
        // Simulate second request - flash should still be there
        $session2 = new Session($session1->toArray());
        expect($session2->hasFlash('message'))->toBeTrue();
        expect($session2->getFlash('message'))->toBe('Hello');
        
        // Simulate third request - flash should be gone
        $session3 = new Session($session2->toArray());
        expect($session3->hasFlash('message'))->toBeFalse();
    });

    test('regular session data persists across requests', function () {
        $session1 = new Session([]);
        $session1->set('user_id', 123);
        
        $session2 = new Session($session1->toArray());
        expect($session2->has('user_id'))->toBeTrue();
        expect($session2->get('user_id'))->toBe(123);
        
        $session3 = new Session($session2->toArray());
        expect($session3->has('user_id'))->toBeTrue();
        expect($session3->get('user_id'))->toBe(123);
    });
});
