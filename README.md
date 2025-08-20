# Middle Framework

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jschreuder/Middle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jschreuder/Middle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jschreuder/Middle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master)

**A micro-framework built around one simple principle: everything should be explicit, replaceable, and safe to change.**

Middle takes a different approach to web application architecture. Instead of magic and conventions, it provides clear interfaces and explicit composition. You can use it to build your organization's perfect framework, not be forced to fit into someone else's choices. Instead of hidden coupling, it enforces clean boundaries. Instead of "getting started quickly," it optimizes for "maintaining confidently."

## Why Middle?

### 🔍 **No Magic, No Surprises**
Every dependency is explicit. Every behavior is visible. No hidden auto-wiring, no surprise side effects, no debugging mysterious framework behavior at 3 AM.

### 🔧 **Everything is Replaceable**
Don't like how routing works? Swap the router. Need custom session handling? Implement `SessionProcessorInterface`. Every component is designed to be replaced without touching anything else.

### 🛡️ **Safe to Change and Extend**
The architecture prevents accidental coupling. You can modify any part of your application with confidence that you're not breaking something unexpected elsewhere.

### 🧪 **Built for Testing**
Interface-driven design means every component can be easily mocked, tested in isolation, and verified independently. No complex test setup, no framework mocking libraries needed.

### How Middle Compares

Like other micro-frameworks, Middle is for composing applications. Unlike them, Middle nudges towards architectural boundaries through interfaces, making SOLID principles and domain-driven design the path of least resistance.

**Choose Middle when:** You want simplicity with architecture meant for SOLID and Domain Driven Development.  
**Choose Others when:** You disagree with the philosophy or convention- or configuration-driven frameworks.

## Core Philosophy

**Explicitness over Convenience**: Middle makes you write a bit more code upfront, but prevents the hidden complexity that accumulates in applications over time.

**Objects over Arrays**: Configuration, data, and behavior are represented by proper classes with clear contracts, not deeply nested arrays or magic properties.

**Immutability by Default**: Adding or removing middleware creates new application instances. No shared mutable state, no spooky action-at-a-distance.

**Standards Compliance**: Full PSR-1, PSR-2, PSR-3, PSR-4, PSR-7, PSR-15, and PSR-17 compliance. Not because we have to, but because standards represent solved problems.

**Minimal Attack Surface**: The framework core is tiny, reducing security vulnerabilities and update risks. Your chosen components update independently.

## Security Through Architecture

### 🔒 **Minimal Attack Surface**
Middle Framework's core contains almost no security-sensitive code. The entire framework is essentially middleware orchestration—no input parsing, no template engines, no ORMs, no file handling. What can't be exploited, won't be exploited.

### 🛡️ **Security by Delegation**
Critical security functionality is handled by specialized, battle-tested libraries that you choose and control:

```php
// Security-critical components are external and replaceable
$app = $app->withMiddleware(
    new RoutingMiddleware(
        new SymfonyRouter($baseUrl),          // Symfony's battle-tested routing
        $fallbackController
    )
);

$app = $app->withMiddleware(
    new SessionMiddleware(
        new LaminasSessionProcessor()         // Laminas's proven session handling
    )
);
```

### 🔄 **Future-Proof Security Updates**
When security vulnerabilities are discovered, updates happen in the concrete libraries—not the framework. Your application code remains unchanged:

- **Symfony Router vulnerability?** → `composer update symfony/routing`
- **Session handling issue?** → `composer update laminas/laminas-session`
- **Template engine exploit?** → `composer update twig/twig`

No framework rewrites, no breaking changes, no security debt.

### **A Framework You'll Never Replace for Security**
Traditional frameworks become security liabilities over time—their monolithic nature means security updates can break your application. Middle's architectural approach minimizes this problem. Security is handled by focused libraries that update independently, while your business logic remains protected behind stable interfaces.

**The result:** A framework with virtually no inherent security vulnerabilities and a security posture that improves over time as underlying libraries mature.

## Composition Over Framework Lock-in

Middle doesn't compete with mature frameworks - it lets you **compose their proven components on your terms**. Instead of accepting a framework's architectural decisions, you define your own interfaces and adapt battle-tested libraries to fit your domain.

```php
// Your domain interface - exactly what your application needs
interface UserRepositoryInterface 
{
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function findActiveUsers(): array;
}

// Adapter that wraps Doctrine behind your interface
class DoctrineUserRepository implements UserRepositoryInterface 
{
    public function __construct(private EntityManagerInterface $em) {}
    
    public function findByEmail(string $email): ?User 
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }
    
    public function save(User $user): void 
    {
        $this->em->persist($user);
        $this->em->flush();
    }
    
    public function findActiveUsers(): array 
    {
        return $this->em->createQuery('SELECT u FROM User u WHERE u.active = true')
                         ->getResult();
    }
}

// Your controllers depend on YOUR interface, not Doctrine's
class UserController implements ControllerInterface 
{
    public function __construct(private UserRepositoryInterface $repository) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface 
    {
        // Clean, domain-focused code
        $users = $this->repository->findActiveUsers();
        // ...
    }
}
```

This approach delivers:

- **Library Independence**: Replace Doctrine with another ORM by implementing your interface
- **Domain Clarity**: Your interfaces reflect business needs, not library abstractions  
- **Future-Proof Evolution**: Library updates only require adapter changes, not application rewrites
- **Focused Testing**: Mock exactly what your application needs, not complex library interfaces

You get mature, battle-tested components (Symfony Routing, Laminas Diactoros) with complete architectural control.

## How It Works

Middle is built around the **middleware pipeline pattern**. Your application is a stack of middleware that processes requests in LIFO (last in, first out) order:

```php
$app = new ApplicationStack(
    new ControllerRunner()              // Executes the matched controller
);

$app = $app->withMiddleware(
    new RoutingMiddleware($router, $fallback)  // Matches routes, adds controller to request
);

$app = $app->withMiddleware(
    new SessionMiddleware($sessionProcessor)   // Adds session to request
);

$app = $app->withMiddleware(
    new ErrorHandlerMiddleware($logger, $errorHandler)  // Catches exceptions
);
```

Each middleware gets a chance to:
- Modify the incoming request
- Pass control to the next middleware
- Modify the outgoing response

## Getting Started

Check out the [Middle skeleton](https://github.com/jschreuder/Middle-skeleton) application for a complete example setup.

### Minimal Setup

```php
<?php
use jschreuder\Middle;

// Create a router with fallback for unmatched routes
$router = new Middle\Router\SymfonyRouter('http://localhost');
$fallbackController = Middle\Controller\CallableController::fromCallable(
    function () {
        return new Laminas\Diactoros\Response\JsonResponse(['error' => 'Not found'], 404);
    }
);

// Build the application stack
$app = new Middle\ApplicationStack(
    new Middle\Controller\ControllerRunner(),
    new Middle\ServerMiddleware\RoutingMiddleware($router, $fallbackController)
);

// Add a route
$router->get('home', '/', 
    Middle\Controller\CallableController::factoryFromCallable(function () {
        return new Laminas\Diactoros\Response\JsonResponse([
            'message' => 'Welcome to Middle Framework'
        ]);
    })
);

// Process requests
$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();
$response = $app->process($request);
(new Laminas\Diactoros\Response\SapiEmitter())->emit($response);
```

## Processing Requests

At its heart, Middle's `ApplicationStackInterface` takes a PSR-7 `ServerRequestInterface` and returns a `ResponseInterface`. Running your application follows this simple pattern:

```php
<?php
// Create PSR-7 request from globals ($_GET, $_POST, $_SERVER, etc.)
$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();

// Process the request through your middleware stack
$response = $app->process($request);

// Send the response to the browser
(new Laminas\Diactoros\Response\SapiEmitter())->emit($response);
```

This separation makes testing trivial and allows you to process requests from any source (CLI, queue workers, etc.).

### Adding Routes

Routes are added directly to the router instance using HTTP method helpers:

```php
// Simple routes with closures
$router->get('home', '/', 
    Middle\Controller\CallableController::factoryFromCallable(function () {
        return new Laminas\Diactoros\Response\JsonResponse(['message' => 'Hello World']);
    })
);

// Routes with parameters and requirements
$router->get('user.profile', '/users/{id}',
    Middle\Controller\CallableController::factoryFromCallable(function ($request) {
        $userId = $request->getAttribute('id');
        return new Laminas\Diactoros\Response\JsonResponse(['user_id' => $userId]);
    }),
    [], // defaults
    ['id' => '\d+'] // requirements - id must be numeric
);

// Routes can use any HTTP method
$router->post('api.users.create', '/api/users', $createUserControllerFactory);
$router->put('api.users.update', '/api/users/{id}', $updateUserControllerFactory);
$router->delete('api.users.delete', '/api/users/{id}', $deleteUserControllerFactory);
```

### Adding Functionality

Middle grows by composition. Need sessions? Add session middleware:

```php
$app = $app->withMiddleware(
    new Middle\ServerMiddleware\SessionMiddleware(
        new Middle\Session\LaminasSessionProcessor()
    )
);
```

Need error handling? Add error middleware:

```php
$errorController = Middle\Controller\CallableController::fromCallable(function ($request) {
    $error = $request->getAttribute('error');
    return new Laminas\Diactoros\Response\JsonResponse([
        'error' => 'Something went wrong'
    ], 500);
});

$app = $app->withMiddleware(
    new Middle\ServerMiddleware\ErrorHandlerMiddleware($logger, $errorController)
);
```

## Advanced Features

### Request Validation and Filtering

Controllers can implement `RequestFilterInterface` and `RequestValidatorInterface` to handle input filtering and validation automatically:

```php
class CreateUserController implements ControllerInterface, RequestFilterInterface, RequestValidatorInterface 
{
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface 
    {
        $data = $request->getParsedBody();
        if (is_array($data)) {
            $data['textfield'] = strip_tags(trim(data['textfield']));
            $request = $request->withParsedBody($data);
        }
        
        return $request;
    }
    
    public function validateRequest(ServerRequestInterface $request): void 
    {
        $data = $request->getParsedBody();
        if (empty($data['email'])) {
            throw new ValidationFailedException(['email' => 'Email is required']);
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFailedException(['email' => 'Invalid email format']);
        }
    }
    
    public function execute(ServerRequestInterface $request): ResponseInterface 
    {
        // Request is guaranteed to be filtered and valid
        $data = $request->getParsedBody();
        // $data['name'] has been stripped of HTML tags
        // $data['email'] has been validated
        // ... create user
    }
}
```

The processing order is: **Filter → Validate → Execute**. This ensures validation always runs on clean data.

### Templating with Views

Middle provides a clean abstraction for rendering responses:

```php
// Setup Twig renderer
$renderer = new Middle\View\TwigRenderer($twig, $responseFactory);

// For redirects, decorate with redirect capability
$renderer = new Middle\View\RedirectRendererMiddleware($renderer, $responseFactory);

// Controllers return View objects
$router->get('profile', '/profile/{id}',
    Middle\Controller\CallableController::factoryFromCallable(
        function ($request) use ($renderer) {
            $userId = $request->getAttribute('id');
            return $renderer->render($request, new Middle\View\View('profile.twig', [
                'user' => $userRepository->find($userId)
            ]));
        }
    )
);

// Redirects are views too - RedirectRendererMiddleware handles status codes 300-399
return $renderer->render($request, new Middle\View\RedirectView('/dashboard', 302));
```

### Custom Exception Handling

Middle includes HTTP-aware exceptions that map to proper response codes:

```php
throw new Middle\Exception\NotFoundException('User not found');        // 404
throw new Middle\Exception\AuthenticationException('Login required');  // 401
throw new Middle\Exception\AuthorizationException('Access denied');    // 403
throw new Middle\Exception\ValidationFailedException($errors);         // 400
```

## Core Components

Middle provides several key interfaces and implementations that handle common application needs:

### Session Management

The `SessionProcessorInterface` provides a clean abstraction for session handling:

```php
// Use Laminas Session (default)
$sessionProcessor = new Middle\Session\LaminasSessionProcessor();

// Or implement your own (Redis, database, etc.)
class RedisSessionProcessor implements SessionProcessorInterface {
    public function processRequest(ServerRequestInterface $request): ServerRequestInterface {
        // Add session to request attributes
    }
    
    public function processResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        // Handle session persistence, cookies, etc.
    }
}
```

The session processor adds a `SessionInterface` instance to the request's `'session'` attribute:

```php
function someController(ServerRequestInterface $request): ResponseInterface {
    $session = $request->getAttribute('session');
    $session->set('user_id', 123);
    $session->setFlash('message', 'Welcome back!');
    // ...
}
```

### Routing

The `RouterInterface` abstracts routing logic and provides reverse URL generation:

```php
$router = new Middle\Router\SymfonyRouter('http://localhost');

// The router supports all HTTP methods
$router->get('home', '/', $controllerFactory);
$router->post('api.users', '/api/users', $controllerFactory);
$router->put('api.user', '/api/users/{id}', $controllerFactory, [], ['id' => '\d+']);
$router->delete('api.user.delete', '/api/users/{id}', $controllerFactory);

// Generate URLs in your application
$urlGenerator = $router->getGenerator();
$userUrl = $urlGenerator->generatePath('api.user', ['id' => 123]); // '/api/users/123'
$fullUrl = $urlGenerator->generateUrl('home', []); // 'http://localhost/'
```

You can organize routes using `RoutingProviderInterface`:

```php
class ApiRoutingProvider implements RoutingProviderInterface {
    public function registerRoutes(RouterInterface $router): void {
        $router->get('api.status', '/api/status', $this->statusControllerFactory());
        $router->post('api.users', '/api/users', $this->createUserControllerFactory());
        // ... more routes
    }
}

$router->registerRoutes(new ApiRoutingProvider());
```

### Request Processing Middleware

Middle includes several middleware for common request processing needs:

```php
// Parse JSON request bodies automatically
$app = $app->withMiddleware(
    new Middle\ServerMiddleware\JsonRequestParserMiddleware()
);

// Automatically validate requests if controller implements RequestValidatorInterface
$app = $app->withMiddleware(
    new Middle\ServerMiddleware\RequestValidatorMiddleware($validationErrorHandler)
);

// Automatically filter requests if controller implements RequestFilterInterface  
$app = $app->withMiddleware(
    new Middle\ServerMiddleware\RequestFilterMiddleware()
);
```

These middleware automatically detect when controllers implement the corresponding interfaces and apply the appropriate processing.

## Dependency Injection Integration

Middle works excellently with DI containers. Here's an example with Pimple:

```php
$container = new Pimple\Container();

$container['app'] = function ($c) {
    return new Middle\ApplicationStack(
        new Middle\Controller\ControllerRunner()
    );
};

// Add middleware through container extension
$container->extend('app', function ($app, $c) {
    return $app->withMiddleware(
        new Middle\ServerMiddleware\RoutingMiddleware(
            $c['router'], $c['fallbackController']
        )
    );
});
```

## When to Choose Middle

**Middle is ideal when you:**
- Value long-term maintainability over short-term convenience
- Want to build your team's standard architecture
- Want explicit, traceable request flow
- Need confidence when refactoring or extending functionality
- Prefer composition over inheritance
- Want every component to be easily testable
- Are building applications that will be maintained by teams over time

**Middle might not be for you if:**
- You need to prototype very quickly
- You prefer convention over configuration
- You want a large ecosystem of plug-and-play components
- You're building simple, short-lived applications

## Requirements

- PHP 8.3+
- PSR-7 implementation (like Laminas Diactoros)
- Symfony Routing component (for the included router)

*Note: All examples use Laminas Diactoros, but any PSR-7 compatible library will work.*

---

**Middle Framework: Explicit. Replaceable. Safe.**
