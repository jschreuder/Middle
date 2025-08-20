---
title: "6. Testing Strategies"
description: "Build test suites that leverage Middle's architecture for reliable unit and integration testing."
layout: default
nav_order: 7
permalink: /chapter6/
---

# Chapter 6: Testing Strategies
*Middle's Explicit Architecture Advantage*

## Introduction

Middle's explicit architecture makes testing remarkably straightforward. No framework mocking, no complex DI container setup, no hidden dependencies to discover. You inject exactly what your class needs and test exactly what it does.

This chapter covers what's unique about testing Middle applications. For general testing patterns, assertions, and Pest PHP syntax, see the excellent [Pest PHP documentation](https://pestphp.com/docs). We'll focus on what makes Middle different: explicit dependencies, middleware testing, and ensuring your interfaces work as expected.

**The key insight:** When everything is explicit, testing becomes simple and reliable.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## Why Middle Makes Testing Easier

### No Framework Mocking Required

**Other frameworks:**
```php
// Complex container and framework mocking
$this->mock('App\SomeFrameworkService', function ($mock) {
    $mock->shouldReceive('frameworkMethod')->andReturn($value);
});

// Or worse - testing against real framework internals
$this->app['some.service']->expects($this->once())->method('doSomething');
```

**Middle:**
```php
// Just inject what you need
$mockRepository = mock(UserRepositoryInterface::class);
$service = new UserRegistrationService($mockRepository);

$mockRepository->shouldReceive('save')->once();
$service->registerUser('test@example.com', 'Test User');
```

No framework to mock, no container to configure - just clean dependency injection.

### Interface-Driven Testing

```php
<?php
// Interface defines behavior through exceptions, not nulls
interface UserRepositoryInterface
{
    /** @throws UserNotFoundException when email doesn't exist */
    public function findByEmail(string $email): User;
    
    public function save(User $user): User;
}

// Test any implementation the same way
test('repository throws exception for missing user', function () {
    $repository = new DatabaseUserRepository($this->db);
    
    expect(fn() => $repository->findByEmail('missing@example.com'))
        ->toThrow(UserNotFoundException::class);
});

// Same test works for all implementations
test('in-memory repository has same behavior', function () {
    $repository = new InMemoryUserRepository();
    
    expect(fn() => $repository->findByEmail('missing@example.com'))
        ->toThrow(UserNotFoundException::class);
});
```

We recommend throwing exceptions and using non-nullable return types - this way you can always be sure the interface will be adhered to. It allows programming the happy flow while catching the error path to deal with, without having to check for nulls in between.

## Testing with Explicit Dependencies

### Service Testing

```php
<?php
// tests/Unit/UserRegistrationServiceTest.php

describe('UserRegistrationService', function () {
    beforeEach(function () {
        $this->userRepository = mock(UserRepositoryInterface::class);
        $this->emailService = mock(EmailServiceInterface::class);
        
        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->emailService
        );
    });
    
    test('registers new user successfully', function () {
        $user = User::create('john@example.com', 'John Doe', 'password123');
        
        $this->userRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn($user);
        
        $this->emailService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->with($user);
        
        $result = $this->service->registerUser('john@example.com', 'John Doe', 'password123');
        
        expect($result)->toBe($user);
    });
    
    test('throws exception when email exists', function () {
        $this->userRepository->shouldReceive('save')
            ->andThrow(new DuplicateEmailException('Email already exists'));
        
        expect(fn() => $this->service->registerUser('existing@example.com', 'Name', 'password'))
            ->toThrow(DuplicateEmailException::class);
    });
});
```

### Controller Testing

```php
<?php
// tests/Unit/RegisterUserControllerTest.php

describe('RegisterUserController', function () {
    beforeEach(function () {
        $this->userService = mock(UserRegistrationService::class);
        $this->controller = new RegisterUserController($this->userService);
    });
    
    test('returns success response', function () {
        $user = User::create('test@example.com', 'Test User', 'password123');
        
        $this->userService->shouldReceive('registerUser')
            ->once()
            ->andReturn($user);
        
        $request = (new ServerRequest([], [], '/register', 'POST'))
            ->withParsedBody(['email' => 'test@example.com', 'name' => 'Test User', 'password' => 'password123']);
        
        $response = $this->controller->execute($request);
        
        expect($response->getStatusCode())->toBe(201);
        
        $body = json_decode((string) $response->getBody(), true);
        expect($body['success'])->toBeTrue();
    });
});
```

## Testing Middleware

Middleware testing is straightforward because each middleware is a pure function with explicit dependencies:

```php
<?php
// tests/Unit/JwtAuthenticationMiddlewareTest.php

describe('JwtAuthenticationMiddleware', function () {
    beforeEach(function () {
        $this->userRepository = mock(UserRepositoryInterface::class);
        $this->middleware = new JwtAuthenticationMiddleware(
            $this->userRepository,
            'test-secret'
        );
    });
    
    test('adds authenticated user to request', function () {
        $user = User::create('test@example.com', 'Test User', 'password123');
        $token = JWT::encode(['user_id' => 1], 'test-secret', 'HS256');
        
        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->andReturn($user);
        
        $request = (new ServerRequest([], [], '/api/protected', 'GET'))
            ->withHeader('Authorization', "Bearer {$token}");
        
        $handler = mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn($req) => $req->getAttribute('authenticated_user') === $user))
            ->andReturn(new Response());
        
        $this->middleware->process($request, $handler);
    });
    
    test('throws exception for invalid token', function () {
        $request = (new ServerRequest([], [], '/api/protected', 'GET'))
            ->withHeader('Authorization', 'Bearer invalid-token');
        
        $handler = mock(RequestHandlerInterface::class);
        
        expect(fn() => $this->middleware->process($request, $handler))
            ->toThrow(AuthenticationException::class);
    });
});
```

## Integration Testing

Test the complete middleware pipeline with your actual service container:

```php
<?php
// tests/Feature/UserRegistrationFlowTest.php

describe('User Registration Flow', function () {
    beforeEach(function () {
        // Use test container with in-memory implementations
        $this->container = new ServiceContainer([
            'db.dsn' => 'sqlite::memory:'
        ]);
        
        $this->app = $this->container->getApp();
        
        // Set up test database
        $this->container->getDb()->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                email TEXT UNIQUE,
                name TEXT,
                password_hash TEXT
            )
        ');
    });
    
    test('registers user through complete pipeline', function () {
        $request = (new ServerRequest([], [], '/users/register', 'POST'))
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody([
                'email' => 'integration@example.com',
                'name' => 'Integration Test',
                'password' => 'securepassword123'
            ]);
        
        $response = $this->app->process($request);
        
        expect($response->getStatusCode())->toBe(201);
        
        // Verify user was actually saved
        $user = $this->container->getUserRepository()->findByEmail('integration@example.com');
        expect($user->getName())->toBe('Integration Test');
    });
});
```

## Test Organization

Keep it simple and mirror your source structure:

```
tests/
├── Unit/                    # Isolated component tests
│   ├── Service/
│   ├── Controller/
│   └── Middleware/
├── Feature/                 # End-to-end tests
└── Support/                 # Test helpers
    └── TestCase.php
```

Create a base test case for shared utilities:

```php
<?php
// tests/Support/TestCase.php

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function createTestUser(array $overrides = []): User
    {
        return User::create(
            $overrides['email'] ?? 'test@example.com',
            $overrides['name'] ?? 'Test User',
            $overrides['password'] ?? 'password123'
        );
    }
    
    protected function createTestDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
```

## Key Takeaways

1. **No Framework Mocking**: Test your code, not the framework. Middle's explicit dependencies make this natural.
2. **Interface Design Matters**: Use exceptions instead of nullable returns to ensure consistent behavior across implementations.
3. **Middleware is Simple**: Each middleware is a pure function - easy to test in isolation.
4. **Integration Tests Verify the Pipeline**: Use your actual service container with test implementations to verify the complete request flow.
5. **Focus on Your Code**: Middle gets out of the way, letting you test your business logic directly.

## Further Reading

- [Pest PHP Documentation](https://pestphp.com/docs) - Complete testing guide and syntax reference
- [Mockery Documentation](http://docs.mockery.io/) - Advanced mocking patterns and techniques

The explicit architecture that makes Middle applications maintainable also makes them testable. When everything is visible and replaceable, testing becomes a natural part of development rather than an afterthought.

## Next Steps

With solid testing practices in place, you're ready to explore common application patterns in Chapter 7. You'll implement authentication, data persistence, and API development using Middle's explicit architecture - all backed by the testing confidence you've built here.

Go on with [Chapter 7](../chapter7/).