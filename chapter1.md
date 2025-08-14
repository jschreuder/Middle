---
title: "1. Architecture Fundamentals"
description: "Learn the middleware pipeline, explicit dependency injection, and interface-driven design that form Middle's foundation."
layout: default
nav_order: 2
permalink: /chapter1/
---

# Chapter 1: Architecture Fundamentals
*Understanding Middle's Core Concepts*

## Introduction

At its heart, Middle is a micro-framework built around two simple but powerful ideas: **PSR-15 middleware** and **make everything explicit**. As such it's almost more of an approach to application building than a framework. It doesn't give you a full toolbox, it gives you an approach and the barebones foundations to make decisions based on you application's needs - instead of a one-size-fits-all (or -fits-none) full scaffold. While other frameworks hide complexity behind conventions and magic methods, Middle puts all the architectural decisions directly in your code where you can see, understand, and modify them. Both right now and in another year or even 5 years on.

This chapter explores the fundamental concepts that make up Middle's identity: the middleware pipeline, explicit dependency injection, and interface-driven design. By the end, you'll understand not just *how* Middle works, but *why* this architectural approach can lead to more maintainable applications.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## The Middleware Pipeline: Request Processing Made Visible

In Middle we make extensive use of the [PSR-7 standard](https://www.php-fig.org/psr/psr-7/) for HTTP requests & responses and the [PSR-15 standard](https://www.php-fig.org/psr/psr-15/) for server handling of those. Basic knowledge of these is assumed in the following text.

### Understanding PSR-15 Middleware

Middle is built on the PSR-15 middleware standard, which defines a simple but powerful pattern for processing HTTP requests. Middleware makes every step of request handling explicit and composable.

```php
<?php
// A simple middleware that logs every request
class RequestLoggerMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Before: Process the incoming request
        $this->logger->info('Processing request: ' . $request->getUri()->getPath());
        
        // Delegate to next middleware in stack
        $response = $handler->handle($request);
        
        // After: Process the outgoing response  
        $this->logger->info('Response status: ' . $response->getStatusCode());
        
        return $response;
    }
}
```

Every middleware follows the same pattern:
1. **Process the incoming request** (for example: authentication, filtering/validation, logging, cache retrieval)
2. **Delegate to the next middleware** in the stack
3. **Process the outgoing response** (for example: compression, cleanup, security headers, cache writing)

This is the power of PSR-15: a standardized, interoperable approach to request processing that works across different frameworks and components. The best middleware should always do only one of these things, and most of the time only before or after the delegation step (not both). It's also perfectly valid to skip delegation when necessary, for example when authentication failed.

By composing multiple simple and testable middleware you can create complex behaviors.

### The ApplicationStack: Explicit Composition

Middle's `ApplicationStack` implements PSR-15. You build your application pipeline by composing middleware in a clear, readable order. All of the middlewares used here are included in Middle. Please note that the ApplicationStack instances are immutable. The `->withMiddleware()` calls create new instances, which is why you must assign them to the `$app` variable.

```php
<?php
// Build your application stack explicitly
$app = new ApplicationStack(
    // Executes a controller when it was set by routing
    new ControllerRunner()
);

// Add middleware in reverse order of execution (LIFO stack)
$app = $app->withMiddleware(
    // Determines what controller should handle the request
    new RoutingMiddleware($router, $fallbackController)
);

$app = $app->withMiddleware(
    // Parses any included JSON blob in request-body into an array
    new JsonRequestParserMiddleware()
);

$app = $app->withMiddleware(
    // Catches exceptions, writes them to logger and runs error controller for user output
    new ErrorHandlerMiddleware($logger, $errorController)
);
```

**Execution Order:** Because middleware uses a LIFO (Last In, First Out) stack, the execution flows:

```
Request  →  ErrorHandler  →  JsonParser  →  Routing  →  Controller
                ↓              ↓            ↓           ↓
Response ←  ErrorHandler  ←  JsonParser  ←  Routing  ←  Controller
```

This explicit ordering means you can see exactly how your requests will be processed by reading the stack construction code. It should be noted that an uncaught exception in any Middleware after ErrorHandler will skip the graceful steps and fall back to the ErrorHandler that catches the exception. And without any catching it will fully crash.

## Explicit Dependency Injection

### Service Container Philosophy

Though Middle is agnostic to which Dependency Injection Container is used, we recommend [Middle DI](https://github.com/jschreuder/Middle-di). Middle DI's approach to dependencies is straightforward: create them explicitly in a service container that acts as the single source of truth for your application's object graph.

```php
<?php
class ServiceContainer
{
    // Provides config() method to use config values given on instantiation
    use ConfigTrait;
    
    // Service definitions are prefixed with "get" and always return the same instance 
    public function getUserRepository(): UserRepositoryInterface
    {
        return new DatabaseUserRepository(
            $this->getDatabase('default')
        );
    }

    public function getUserService(): UserService  
    {
        return new UserService($this->getUserRepository());
    }
    
    // You can also add 1 parameter that names the instance
    // They will still always return the same instance per name
    public function getDatabase(string $name): PDO
    {
        // Only as an example, please check if name is valid in production code
        return new PDO(
            $this->config('db.'.$name.'.dsn'),
            $this->config('db.'.$name.'.user'),
            $this->config('db.'.$name.'.pass'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    // Any other method will be run as-is, by convention those should be
    // prefixed with "new", they should be considered factories
    public function newUser(string $email, string $password): User
    {
        return new User($email, $password);
    }
}
```

And after this to use the container:

```php
<?php
// Middle-DI compiles for development like this
$container = (new DiCompiler(ServiceContainer::class))
    ->compile()
    ->newInstance($config);

// Middle-DI for production with everything cached, remember to delete the 
// cached file on deployment
$container = (new DiCachedCompiler(
    new DiCompiler(ServiceContainer::class),
    new SplFileObject('var/cache/container.php', 'c+')
))->compile()->newInstance($config);

// Full IDE support with zero runtime overhead
$database = $container->getDatabase(); // Returns PDO, full autocompletion
$userService = $container->getUserService(); // Returns UserService, type-safe
```

### Middle-DI Benefits

- **Complete IDE Support**: Full autocompletion and "Go to Definition"
- **Zero Configuration**: Simple conventions - `get*()` for singletons, `new*()` for factories
- **Compile-Time Safety**: Type errors caught during development, not production
- **Zero Runtime Overhead**: Generates cached PHP code optimized for opcache in production setup

**But It's Optional**: Middle works with any container approach. Use Symfony's DI, PHP-DI, Pimple, or even manual construction - Middle's interfaces don't care how services are created.

## Interface-Driven Architecture

### Why Interfaces Matter

Interfaces are the foundation of maintainable architecture. They define contracts between components, allowing you to change implementations without affecting dependent code. Middle encourages interface-driven design. This helps to abstract away concrete implementations, but should be done judiciously in a way that limits necessary rewrites when needing to replace or upgrade dependencies.

```php
<?php
// Define in the interface what your application needs, not what dependencies provide
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function findActive(): array;
}

// Implementation can change without affecting controllers
class DatabaseUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $db) {}
    
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? User::fromArray($data) : null;
    }
    
    // ... other methods
}

// Controllers depend on interfaces, not implementations
class UserController implements ControllerInterface
{
    public function __construct(
        private UserRepositoryInterface $repository
    ) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('id');
        $user = $this->repository->findById($userId);
        
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        
        return new JsonResponse(['user' => $user->toArray()]);
    }
}
```

### Interface Benefits in Practice

**Swappable Implementations:**
```php
<?php
// Production: Database repository
$container->getUserRepository(); // → DatabaseUserRepository

// As an example: you could even have an in-memory repository and could also
// become Doctrine without the users of the repository knowing
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    
    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }
    
    public function save(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }
}

// Same controller works with all implementations of the interface
```

**Clear Testing:**
```php
<?php
// Test only what your application uses
$mockRepository = $this->createMock(UserRepositoryInterface::class);
$mockRepository->expects($this->once())
    ->method('findById')
    ->with(123)
    ->willReturn($user);

$controller = new UserController($mockRepository);
```

## Middleware Composition Patterns

### Environment-Based Composition

Different environments could need different middleware stacks:

```php
<?php
// only the getApp() method shown, other methods need implementation as well
public function getApp(): ApplicationStack
{
    $app = new ApplicationStack(new ControllerRunner())
        ->withMiddleware(new RoutingMiddleware($this->getRouter(), $this->get404Handler()));
    
    // Add environment-specific middleware
    if ($this->config('app.environment') === 'development') {
        $app = $app->withMiddleware(new DebugMiddleware($this->getDebugger()));
    }
    
    if ($this->config('features.cache_responses')) {
        $app = $app->withMiddleware(new CacheMiddleware($this->getCache()));
    }
    
    // Error handling should be outermost
    return $app->withMiddleware(
        new ErrorHandlerMiddleware($this->getLogger(), $this->get500Handler())
    );
}
```

## Real-World Example: Building a Complete Stack

Let's build a realistic application stack to see how these concepts work together:

```php
<?php
class ServiceContainer
{
    use ConfigTrait;
    
    public function getApp(): ApplicationStack
    {
        // Start with the core
        $app = new ApplicationStack(new ControllerRunner());
        
        // Add routing
        $app = $app->withMiddleware(
            new RoutingMiddleware($this->getRouter(), $this->get404Handler())
        );
        
        // Add request processing for JSON input
        $app = $app->withMiddleware(new JsonRequestParserMiddleware());
        
        // Add error handling (outermost layer)
        return $app->withMiddleware(
            new ErrorHandlerMiddleware($this->getLogger(), $this->getErrorHandler())
        );
    }
    
    public function getRouter(): RouterInterface
    {
        return new SymfonyRouter($this->config('site.url'));
    }
    
    public function get404Handler(): ControllerInterface
    {
        // cleaner in their own file, but using anonymous class for simplicity in the example
        return new class implements ControllerInterface {
            public function execute(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['message' => 'Not found'], 404);
            }
        };
    }
    
    public function get500Handler(): ControllerInterface
    {
        // cleaner in their own file, but using anonymous class for simplicity in the example
        return new class implements ControllerInterface {
            public function execute(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse(['message' => 'Server error'], 500);
            }
        };
    }

    public function getAuthService(): AuthenticationServiceInterface
    {
        return new JwtAuthenticationService(
            $this->getUserRepository(),
            $this->config('jwt.secret')
        );
    }
    
    public function getUserRepository(): UserRepositoryInterface
    {
        return new DatabaseUserRepository($this->getDatabase());
    }
    
    public function getDatabase(): PDO
    {
        return new PDO(
            $this->config('db.dsn'),
            $this->config('db.user'),
            $this->config('db.pass'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
```

## Best Practices

### ✅ Do: Keep Middleware Focused

```php
<?php
// Good: Single responsibility
class JsonRequestParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isJsonRequest($request)) {
            $request = $request->withParsedBody($this->parseJsonBody($request));
        }
        
        return $handler->handle($request);
    }
}
```

### ✅ Do: Use Descriptive Service Names

```php
<?php
class ServiceContainer
{
    public function getUserRegistrationService(): UserRegistrationService {} // Clear purpose
    public function getEmailVerificationMailer(): EmailServiceInterface {}   // Specific role
    public function getProductCatalogRepository(): ProductRepositoryInterface {} // Clear domain
}
```

### ✅ Do: Design Interfaces Around Use Cases

```php
<?php
// Good: Interface matches how it's actually used
interface UserAuthenticationInterface
{
    public function authenticateByCredentials(string $email, string $password): ?User;
    public function authenticateByToken(string $token): ?User;
    public function generateAuthToken(User $user): string;
    public function invalidateToken(string $token): void;
}
```

## Key Takeaways

1. **Explicitness Over Convention**: Middle makes architectural decisions visible in code rather than hiding them in configuration or magic methods
2. **PSR Standards Compliance**: Built on proven standards for HTTP messaging, middleware, and logging, ensuring interoperability and best practices
3. **Middleware Pipeline**: Request processing happens through an explicit, composable pipeline that you can see, test, and modify
4. **Interface-Driven Design**: Depend on interfaces, not implementations, to create maintainable boundaries between components
5. **Service Container**: Explicit dependency creation provides clarity and debuggability
6. **Debugging-Friendly**: Every step of request processing is visible and traceable in backtraces

## Next Steps

Now that you understand Middle's architectural foundation, you're ready to start building. The fastest way to get started is with the **Middle Skeleton** - a foundational setup that demonstrates all these architectural principles in a working application. In Chapter 2, we'll walk through creating your first Middle application from scratch, implementing a complete feature that demonstrates these architectural principles in practice.

The key insight to take forward: Middle optimizes for the long term. While it requires more explicit code upfront, this investment pays dividends in maintainability, debuggability, and team collaboration as your application grows.

Go on with [Chapter 2](../chapter2/).