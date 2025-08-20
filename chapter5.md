---
title: "5. Secure by Design"
description: "Leverage PHP's process isolation and Middle's minimal attack surface for robust security. Learn defensive programming patterns, security-first interface design, and how explicit architecture prevents common vulnerabilities."
layout: default
nav_order: 6
permalink: /chapter5/
---

# Chapter 5: Secure by Design
*Defensive Programming and Architectural Security*

## Introduction

Security isn't a feature you add later—it's an architectural decision you make from the beginning. Middle's explicit architecture naturally promotes secure design patterns by making every dependency visible, every data flow traceable, and every security boundary clear.

This chapter explores how Middle's core principles create inherently more secure applications. You'll see how PHP's process isolation model, when combined with Middle's minimal attack surface and explicit composition, creates robust defense against common vulnerabilities.

We'll build upon the patterns you've already learned—the middleware from Chapter 4 and the interface design from Chapter 3 to create applications that are secure by design, not by accident.

**The key insight:** Security emerges naturally from Middle's architectural principles. When everything is explicit and replaceable, security becomes predictable and auditable.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## PHP's Unique Security Advantages

### Process Isolation and the Shared-Nothing Architecture

PHP's request lifecycle provides inherent security advantages that you should consciously leverage in your security model. Unlike long-running server applications, PHP starts fresh for every request—no shared state between requests, no memory leaks across user sessions, no cross-contamination between different user contexts.

```php
<?php
// Every request starts with a clean slate
$container = new ServiceContainer($config);
$app = $container->getApp();

// Process request in isolation
$request = ServerRequestFactory::fromGlobals();
$response = $app->process($request);

// Everything dies when the request ends - no lingering state
```

**Security Benefits:**
- **Memory isolation**: One user's data can't leak into another user's request
- **State isolation**: No shared mutable state between requests
- **Failure isolation**: One request crash doesn't affect other users
- **Resource cleanup**: Automatic cleanup prevents resource exhaustion attacks

### Building Security Around Process Isolation

When designing your security model, consciously leverage PHP's process isolation while being aware of components that bridge requests. Sessions, for example, deliberately break isolation to maintain state—treat them as security boundaries that need special attention.

Design your services to be stateless within each request, storing persistent data only through explicit interfaces like repositories. This makes security auditing straightforward: trace the data flow through explicit dependencies rather than hunting for hidden shared state.

## Middle's Security-First Architecture

### Minimal Attack Surface

Traditional frameworks ship with hundreds of classes handling everything from file uploads to template engines to ORM systems. Each component is a potential vulnerability vector. Middle takes the opposite approach—the framework core is tiny, containing almost no security-sensitive code.

**What Middle's core contains:**
- Middleware composition logic
- PSR-7 request/response handling 
- Basic routing interfaces
- Session management interfaces

**What you add through composer and integrate:**
- Input parsing (use PHP's built-in `json_decode` or libraries like Laminas Serializer)
- Template engines (Twig, Plates, or custom renderers)
- Database access (PDO, Doctrine, or custom repositories)
- File handling (use Flysystem or custom implementations)
- Validation (Symfony Validator, Respect Validation, or custom logic)

**What can't be exploited won't be exploited.** Middle delegates security-critical functionality to specialized libraries that update independently.

### Explicit Dependencies Prevent Hidden Vulnerabilities

Hidden dependencies are security vulnerabilities waiting to happen. When you don't know what your code depends on, you can't secure it. Middle's explicit dependency injection makes every security boundary visible:

```php
<?php
class UserRegistrationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,    // Data access - audit this
        private EmailServiceInterface $emailService,        // External service - secure this  
        private PasswordHasherInterface $passwordHasher     // Crypto - validate this
    ) {}
}
```

You can audit this service's security by examining three interfaces. No hidden file system access, no surprise database queries, no implicit email sending.

### Security Through "Proudly Found Elsewhere"

Middle delegates security-critical functionality to specialized, battle-tested libraries. As discussed in Chapter 3, good interface design allows you to safely depend on external libraries without tight coupling. When vulnerabilities are discovered, updates happen in focused libraries—not the framework:

- **Vulnerability in password hashing?** → Update PHP version
- **JWT vulnerability?** → `composer update firebase/php-jwt`  
- **Session vulnerability?** → `composer update laminas/laminas-session`
- **Routing vulnerability?** → `composer update symfony/routing`

Your application code remains unchanged—security improves without breaking changes. The interface-driven architecture ensures you can even swap entire libraries if needed without affecting your business logic.

## Defensive Input Handling

### Leveraging Middle's Built-in Security Patterns

Chapter 4 introduced `RequestFilterInterface` and `RequestValidatorInterface` for automatic request processing. These patterns are inherently security-focused: filtering removes dangerous content before validation, and validation ensures business rules are enforced consistently.

The key security insight is the processing order: **Filter → Validate → Execute**. This ensures validation always works on clean data, preventing scenarios where validation passes on filtered input but business logic processes dangerous unfiltered data.

When implementing these interfaces, focus on:
- **Whitelisting safe content** rather than blacklisting dangerous patterns
- **Using proven filtering libraries** like Laminas Filter for HTML sanitization
- **Validating data types and ranges** to prevent injection and overflow attacks
- **Normalizing input** (trim whitespace, normalize case) before validation

### Error Handling That Doesn't Leak Information

Secure error handling provides useful feedback without revealing system internals:

```php
<?php
class SecurityAwareErrorController implements ControllerInterface
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $error = $request->getAttribute('error');
        
        // Log full details for developers
        $this->logger->error('Application error', [
            'exception' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString()
        ]);
        
        // Return safe error response to users
        return new JsonResponse([
            'success' => false,
            'message' => $this->getSafeErrorMessage($error)
        ], $this->getStatusCode($error));
    }
    
    private function getSafeErrorMessage(\Throwable $error): string
    {
        return match(true) {
            $error instanceof ValidationFailedException => 'Validation failed',
            $error instanceof AuthenticationException => 'Authentication required',
            $error instanceof NotFoundException => 'Resource not found',
            default => 'An error occurred'
        };
    }
}
```

Use logs for debugging information, not user-facing error messages. Detailed errors should never reach users, even in development environments. Good defensive programming means avoiding development modes that might accidentally remain enabled in production—configuration errors are a common source of information disclosure vulnerabilities.

## Security-First Interface Design

### Exceptions Instead of Nulls for Security

Chapter 3 demonstrated why exceptions are superior to nullable returns for interface design. From a security perspective, this pattern prevents catastrophic failures where null checks are forgotten:

```php
<?php
// ❌ Null return can be ignored - security bypass!
$user = $this->userRepository->findByEmail($email);
return $user->verifyPassword($password); // Forgot null check!

// ✅ Exception forces explicit handling - secure by design
try {
    $user = $this->userRepository->findByEmail($email);
    return $user->verifyPassword($password);
} catch (UserNotFoundException $e) {
    return false; // Must handle explicitly
}
```

Exceptions make security failures visible and force explicit error handling, preventing silent bypasses of security checks.

### Value Objects for Security Validation

In Chapter 7 we will introduce value objects for domain primitives, which also helps secure your data. These are particularly powerful for security because they enforce validation at construction time:

```php
<?php
class Email
{
    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: {$email}");
        }
        $this->value = strtolower(trim($email));
    }
}
```

Once an `Email` object exists, you know it contains a valid email address. This prevents injection attacks and ensures consistent validation across your application.

### Interface Contracts That Enforce Security

Design interfaces that make insecure usage impossible:

```php
<?php
interface SecureUserRepositoryInterface
{
    public function findByEmail(Email $email): User;           // Type-safe email
    public function save(User $user): User;                    // Only domain objects
    public function authenticateUser(Email $email, string $password): ?User; // Clear intent
}
```

By accepting value objects instead of primitives, interfaces enforce validation. By returning domain objects instead of arrays, they prevent data structure confusion that can lead to vulnerabilities.

## Authentication Architecture Patterns

### Comparing Security Models

Chapter 4 introduced JWT middleware and Chapter 7 will demonstrate session authentication. Each has distinct security characteristics:

**JWT Security Trade-offs:**
- ✅ Stateless, scalable across servers
- ❌ Cannot revoke tokens before expiration
- ❌ Single secret compromise affects all tokens

**Session Security Trade-offs:**
- ✅ Immediate revocation capability  
- ✅ Secret rotation without user impact
- ❌ Requires shared storage for scaling

Choose based on your security requirements: JWTs for stateless APIs where revocation isn't critical, sessions for applications requiring immediate access control.

### Defense in Depth with Middleware Composition

Layer multiple security mechanisms through Middle's explicit middleware composition:

```php
<?php
public function getApp(): ApplicationStack
{
    return new ApplicationStack(new ControllerRunner())
        ->withMiddleware(new JsonRequestParserMiddleware())         // Layer 1: Parse input
        ->withMiddleware(new RequestValidatorMiddleware($handler))  // Layer 2: Validate input (executes second)
        ->withMiddleware(new RequestFilterMiddleware())             // Layer 3: Filter input (executes first)
        ->withMiddleware(new AuthorizationMiddleware($authzService)) // Layer 4: Authorize (executes second)
        ->withMiddleware(new AuthenticationMiddleware($authService)) // Layer 5: Authenticate (executes first)
        ->withMiddleware(new RoutingMiddleware($router, $fallback))  // Layer 6: Route
        ->withMiddleware(new ErrorHandlerMiddleware($logger, $errorController)); // Layer 7: Handle errors
}
```

Each layer provides different protection. If one layer fails, others remain to defend the application.

## Security Testing Strategies

### Testing Security Scenarios

Chapter 6 will cover Middle's testing advantages. For security, focus on testing that architectural patterns actually prevent vulnerabilities:

```php
<?php
test('authentication middleware rejects expired sessions', function () {
    $session = mock(SessionInterface::class);
    $session->shouldReceive('get')->with('last_activity')->andReturn(time() - 3600);
    $session->shouldReceive('destroy')->once();
    
    $request = (new ServerRequest())->withAttribute('session', $session);
    
    expect(fn() => $this->authMiddleware->process($request, $handler))
        ->toThrow(AuthenticationException::class);
});

test('input filtering prevents XSS attacks', function () {
    $request = (new ServerRequest())->withParsedBody([
        'name' => '<script>alert("xss")</script>John'
    ]);
    
    $filtered = $this->controller->filterRequest($request);
    
    expect($filtered->getParsedBody()['name'])->toBe('John');
});
```

Test the security properties of your interfaces, not just their functional behavior.

### Integration Testing for Security

Test complete security scenarios end-to-end:

```php
<?php
test('prevents authentication bypass through parameter manipulation', function () {
    $response = $this->app->process(
        (new ServerRequest([], [], '/admin/users', 'GET'))
            ->withQueryParams(['user_id' => 'admin'])  // Attempt bypass
    );
    
    expect($response->getStatusCode())->toBe(401); // Should require authentication
});
```

Integration tests verify that your layered security actually works together to prevent real attack scenarios.

## Key Takeaways

1. **Leverage PHP's Process Isolation**: Consciously design around PHP's shared-nothing architecture while securing components that bridge requests like sessions
2. **Minimal Attack Surface**: Middle's tiny core delegates security to specialized libraries that update independently
3. **Explicit Dependencies**: Every security boundary is visible and auditable through dependency injection
4. **Security by Design**: Use patterns from other chapters—exceptions over nulls, value objects, interface contracts—to make insecure usage impossible
5. **Defense in Depth**: Layer security mechanisms through explicit middleware composition
6. **Test Security Properties**: Verify that architectural patterns actually prevent vulnerabilities

## Next Steps

With security woven into your application's architecture, you're ready to master comprehensive testing strategies in Chapter 6. The explicit dependencies and predictable behavior that make Middle applications secure also make them thoroughly testable.

Remember: **Security isn't a feature you add—it's an architectural principle you follow.** Middle's explicit architecture makes security decisions visible, auditable, and maintainable.

Go on with [Chapter 6](../chapter6/).