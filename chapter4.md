---
title: "Chapter 4"
description: "Middleware Deep Dive"
layout: default
nav_order: 5
permalink: /chapter4/
---

# Chapter 4: Middleware Deep Dive
*Building Custom Application Behavior*

## Introduction

In previous chapters, you've seen middleware in action: parsing JSON requests, handling routing, and managing errors. But middleware's true power lies in building custom behavior that cuts across your entire application. Authentication, request validation, response transformation - these cross-cutting concerns are where middleware shines.

This chapter teaches you to build sophisticated middleware using Middle's explicit composition approach. You'll learn when to use middleware versus when to leverage infrastructure, how to integrate external libraries cleanly, and how to compose middleware pipelines that are both powerful and maintainable.

The key insight: **Middle's explicit middleware composition makes complex request processing predictable and debuggable**.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## Understanding Middleware Deeply

### The Middleware Contract

Every Middle middleware implements the [PSR-15](https://www.php-fig.org/psr/psr-15/) `MiddlewareInterface`:

```php
<?php
interface MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

This simple contract enables powerful composition. Let's examine what happens inside:

```php
<?php
class ExampleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // PHASE 1: INCOMING REQUEST PROCESSING
        // Modify request, validate, extract data, set up context
        $modifiedRequest = $this->processIncomingRequest($request);
        
        // PHASE 2: DELEGATION
        // Pass control to next middleware/controller
        $response = $handler->handle($modifiedRequest);
        
        // PHASE 3: OUTGOING RESPONSE PROCESSING  
        // Modify response, add headers, log results, clean up
        $modifiedResponse = $this->processOutgoingResponse($response, $modifiedRequest);
        
        return $modifiedResponse;
    }
}
```

### Request Flow Visualization

```
Middleware Stack: [ErrorHandler, Auth, Router, Validation, Controller]

Request  →  ErrorHandler  →  Auth  →  Router  →  Validation  →  Controller
            ↓                ↓        ↓          ↓              ↓
Response ←  ErrorHandler  ←  Auth  ←  Router  ←  Validation  ←  Controller
```

Each middleware gets two opportunities to affect the request/response cycle - once on the way in, once on the way out.

## Choosing the Right Layer

Before building middleware, understand what belongs where:

### Infrastructure Level
Handle these with your deployment infrastructure (nginx, load balancer, API gateway):
- **Rate limiting** - nginx `limit_req`, AWS API Gateway throttling
- **DDoS protection** - Cloudflare, AWS Shield
- **SSL termination** - Load balancer, reverse proxy
- **Geographic blocking** - CDN, firewall rules

### Middleware Level  
Handle these with Middle middleware:
- **Authentication** - Verifying user identity
- **Request parsing** - JSON, form data transformation
- **CORS headers** - Cross-origin request handling
- **Application caching** - Response caching based on business logic

### Business Logic Level
Handle these in your controllers and services:
- **Domain validation** - Business rule enforcement
- **Data transformation** - Converting between formats
- **Authorization** - Permission checking based on business rules

Don't implement in PHP what your infrastructure does better. You could probably implement some rate limiting and geo-blocking features in PHP, but it would be very inefficient and far less effective. Also don't put business logic in the middleware, this would be used on every request and only belongs there if it's actually needed often enough.

## Authentication Middleware

Authentication middleware verifies user identity and adds user context to requests. Let's build a JWT authentication middleware that integrates with external libraries rather than implementing everything from scratch.

We'll use the Firebase JWT library, which handles the complex cryptographic operations:

```php
<?php
// Use Firebase JWT library: composer require firebase/php-jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private string $jwtSecret,
        private array $publicRoutes = []
    ) {}
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isPublicRoute($request)) {
            return $handler->handle($request);
        }
        
        $token = $this->extractToken($request);
        if (!$token) {
            throw new AuthenticationException('Authentication token required');
        }
        
        try {
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $user = $this->userRepository->findById($payload->user_id);
            
            if (!$user) {
                throw new AuthenticationException('User not found');
            }
            
            // Add authenticated user to request context
            $authenticatedRequest = $request->withAttribute('authenticated_user', $user);
            
            return $handler->handle($authenticatedRequest);
            
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid authentication token');
        }
    }
    
    private function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function isPublicRoute(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        
        foreach ($this->publicRoutes as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }
}
```

This middleware demonstrates Middle's strength: the authentication logic is completely visible and testable. There's no framework magic determining which routes need authentication or how tokens are validated.

## Request Validation Using Middle's Built-in System

Middle provides `RequestValidatorInterface` and `RequestFilterInterface` that controllers can implement for automatic request processing. When you add these middleware to your application stack, they detect controllers implementing these interfaces and automatically call their methods.

### Input Filtering for Security

Input filtering sanitizes and normalizes incoming data before validation. Controllers implementing `RequestFilterInterface` have their `filterRequest` method called automatically. For production applications, we recommend dedicated filtering libraries like Laminas Filter:

```php
<?php
use jschreuder\Middle\Controller\RequestFilterInterface;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StripTags;

class CreateUserController implements ControllerInterface, RequestFilterInterface
{
    public function filterRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return $request;
        }
        
        // Use dedicated filtering library for security
        $nameFilter = (new FilterChain())
            ->attach(new StringTrim())
            ->attach(new StripTags());
        $emailFilter = (new FilterChain())
            ->attach(new StringTrim())
            ->attach(new StringToLower());
        $filtered = [
            'email'    => $emailFilter->filter($data['email'] ?? ''),
            'name'     => $nameFilter->filter($data['name'] ?? ''),
            'password' => $data['password'] ?? '' // Never filter passwords
        ];
        
        return $request->withParsedBody($filtered);
    }
    
    // ... validation and execute methods
}
```

### Validation with External Libraries

Controllers implementing `RequestValidatorInterface` have their `validateRequest` method called automatically. For validation, we can leverage mature libraries like Symfony Validator to implement Middle's automatic validation system:

```php
<?php
// composer require symfony/validator
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;

class CreateUserController implements ControllerInterface, RequestValidatorInterface
{
    public function __construct(
        private UserService $userService,
        private ValidatorInterface $validator
    ) {}
    
    public function validateRequest(ServerRequestInterface $request): void
    {
        $data = $request->getParsedBody() ?? [];
        
        // Define validation constraints using Symfony Validator
        $constraints = new Assert\Collection([
            'email' => [new Assert\Email(), new Assert\NotBlank()],
            'name' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
            'password' => [new Assert\Length(min: 8)]
        ]);
        
        $violations = $this->validator->validate($data, $constraints);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new ValidationFailedException($errors);
        }
    }
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        // Request is guaranteed to be valid by the time we reach here
        $data = $request->getParsedBody();
        $user = $this->userService->createUser($data['email'], $data['name'], $data['password']);
        
        return new JsonResponse(['user' => $user->toArray()], 201);
    }
}
```

The beauty of this approach is that filtering happens before validation, ensuring validation works on clean data. Both processes are automatic when the appropriate middleware is in your application stack, but the logic remains explicit and testable in your controllers.

## Response Modification Middleware

Response modification middleware processes requests normally but modifies the response before sending it to the client. This is ideal for adding headers, handling CORS, or implementing application-level caching.

### CORS Middleware

Cross-Origin Resource Sharing (CORS) middleware handles browser preflight requests and adds appropriate headers to responses. Here's a practical implementation:

```php
<?php
class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private array $config = [])
    {
        // Set sensible defaults
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
            'max_age' => 86400
        ], $config);
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight OPTIONS request immediately
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }
        
        // Process normal request and add CORS headers to response
        $response = $handler->handle($request);
        return $this->addCorsHeaders($request, $response);
    }
    
    private function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        
        return new Response(200, [
            'Access-Control-Allow-Origin' => $this->isOriginAllowed($origin) ? $origin : '',
            'Access-Control-Allow-Methods' => implode(', ', $this->config['allowed_methods']),
            'Access-Control-Allow-Headers' => implode(', ', $this->config['allowed_headers']),
            'Access-Control-Max-Age' => (string) $this->config['max_age']
        ]);
    }
    
    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }
        
        return $response;
    }
    
    private function isOriginAllowed(string $origin): bool
    {
        // Application-specific origin validation logic
        return in_array('*', $this->config['allowed_origins']) || 
               in_array($origin, $this->config['allowed_origins']);
    }
}
```

### Application-Level Response Caching

Sometimes you need caching logic that understands your application's business rules rather than just HTTP headers. Here's response cache middleware that integrates with your routing and user context:

```php
<?php
class ResponseCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $defaultTtl = 300,
        private array $cacheableRoutes = []
    ) {}
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->shouldCache($request)) {
            return $handler->handle($request);
        }
        
        $cacheKey = $this->generateCacheKey($request);
        
        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $this->unserializeResponse($cached)
                       ->withHeader('X-Cache-Status', 'HIT');
        }
        
        // Generate and cache response
        $response = $handler->handle($request);
        
        if ($response->getStatusCode() === 200) {
            $this->cache->set($cacheKey, $this->serializeResponse($response), $this->defaultTtl);
        }
        
        return $response->withHeader('X-Cache-Status', 'MISS');
    }
    
    private function shouldCache(ServerRequestInterface $request): bool
    {
        // Application logic for when to cache - check business rules
        return $request->getMethod() === 'GET' && 
               !$request->hasHeader('Authorization') &&
               in_array($request->getAttribute('_route'), $this->cacheableRoutes);
    }
    
    private function generateCacheKey(ServerRequestInterface $request): string
    {
        // Application-specific cache key generation
        return 'response:' . md5($request->getUri()->getPath());
    }
    
    private function serializeResponse(ResponseInterface $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody()
        ];
    }
    
    private function unserializeResponse(array $cached): ResponseInterface
    {
        return new Response($cached['body'], $cached['status'], $cached['headers']);
    }
}
```

## Middleware Composition Patterns

This is where Middle truly shines - explicit, visible composition that makes complex request processing predictable and maintainable.

### Environment-Based Composition

Different environments need different middleware. Middle makes this explicit in your service container:

```php
<?php
class ServiceContainer
{
    public function getApp(): ApplicationStack
    {
        $app = new ApplicationStack(new ControllerRunner());
        
        // Core middleware - always needed
        $app = $app->withMiddleware(new JsonRequestParserMiddleware())
                   ->withMiddleware(new RoutingMiddleware($this->getRouter(), $this->get404Handler()));
        
        // Add Middle's built-in request processing
        $app = $app->withMiddleware(new RequestFilterMiddleware())
                   ->withMiddleware(new RequestValidatorMiddleware($this->getValidationErrorHandler()));
        
        // Environment-specific middleware
        if ($this->config('app.environment') === 'development') {
            $app = $app->withMiddleware(new DebugMiddleware());
        }
        
        if ($this->config('features.authentication')) {
            $app = $app->withMiddleware(new JwtAuthenticationMiddleware(
                $this->getUserRepository(),
                $this->config('jwt.secret'),
                ['/api/auth/.*', '/health']
            ));
        }
        
        if ($this->config('features.cors')) {
            $app = $app->withMiddleware(new CorsMiddleware($this->config('cors')));
        }
        
        // Error handling - outermost layer
        $app = $app->withMiddleware(new ErrorHandlerMiddleware(
            $this->getLogger(),
            $this->get500Handler()
        ));
        
        return $app;
    }
    
    private function getValidationErrorHandler(): \Closure
    {
        return function (ServerRequestInterface $request, ValidationFailedException $exception): ResponseInterface {
            return new JsonResponse([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $exception->getValidationErrors()
            ], 400);
        };
    }
}
```

### Layered Application Architecture

For larger applications, you can create different application stacks for different purposes:

```php
<?php
class ServiceContainer
{
    public function getApiApp(): ApplicationStack
    {
        // API-specific middleware stack
        return new ApplicationStack(new ControllerRunner())
            ->withMiddleware(new RequestValidatorMiddleware($this->getApiErrorHandler()))
            ->withMiddleware(new RequestFilterMiddleware())
            ->withMiddleware(new JsonRequestParserMiddleware())
            ->withMiddleware(new CorsMiddleware($this->config('cors')))
            ->withMiddleware(new JwtAuthenticationMiddleware(
                $this->getUserRepository(),
                $this->config('jwt.secret')
            ));
    }
    
    public function getWebApp(): ApplicationStack
    {
        // Web-specific middleware stack  
        return new ApplicationStack(new ControllerRunner())
            ->withMiddleware(new SessionMiddleware($this->getSessionProcessor()));
    }
    
    public function getMainApp(): ApplicationStack
    {
        // Route to appropriate sub-application
        return new ApplicationStack(new ControllerRunner())
            ->withMiddleware(new SubApplicationRouter([
                '/api' => $this->getApiApp(),
                '/' => $this->getWebApp()
            ]))
            ->withMiddleware(new ErrorHandlerMiddleware(
                $this->getLogger(),
                $this->getErrorHandler()
            ));
    }
}
```

## Testing Middleware

Testing middleware in Middle is straightforward because dependencies are explicit and behavior is predictable.

### Unit Testing Individual Middleware

Each middleware can be tested in isolation by mocking its dependencies:

```php
<?php
// tests/Unit/Middleware/JwtAuthenticationMiddlewareTest.php

beforeEach(function () {
    $this->userRepository = mock(UserRepositoryInterface::class);
    $this->middleware = new JwtAuthenticationMiddleware(
        $this->userRepository,
        'test-secret',
        ['/public/.*']
    );
});

test('allows public routes without authentication', function () {
    $request = new ServerRequest([], [], '/public/health', 'GET');
    $handler = createMockHandler();
    
    $response = $this->middleware->process($request, $handler);
    
    expect($response->getStatusCode())->toBe(200);
});

test('validates JWT token and adds user to request', function () {
    $user = createUser();
    $token = JWT::encode(['user_id' => 1], 'test-secret', 'HS256');
    
    $this->userRepository->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($user);
    
    $request = (new ServerRequest([], [], '/api/protected', 'GET'))
        ->withHeader('Authorization', "Bearer {$token}");
    
    $handler = mock(RequestHandlerInterface::class);
    $handler->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(function ($req) use ($user) {
            return $req->getAttribute('authenticated_user') === $user;
        }))
        ->andReturn(new Response());
    
    $this->middleware->process($request, $handler);
});

test('throws exception for invalid token', function () {
    $request = (new ServerRequest([], [], '/api/protected', 'GET'))
        ->withHeader('Authorization', 'Bearer invalid-token');
    
    $handler = createMockHandler();
    
    expect(fn() => $this->middleware->process($request, $handler))
        ->toThrow(AuthenticationException::class, 'Invalid authentication token');
});

function createMockHandler(): RequestHandlerInterface
{
    $handler = mock(RequestHandlerInterface::class);
    $handler->shouldReceive('handle')->andReturn(new Response());
    return $handler;
}

function createUser(): User
{
    return User::create('test@example.com', 'Test User', 'password123');
}
```

### Testing Controllers with Built-in Middleware

Testing controllers that use Middle's filtering and validation is straightforward:

```php
<?php
// tests/Unit/Controller/CreateUserControllerTest.php

beforeEach(function () {
    $this->userService = mock(UserService::class);
    $this->controller = new CreateUserController($this->userService);
});

test('filtering and validation work together', function () {
    // Test that filtering cleans data appropriately
    $request = (new ServerRequest())
        ->withParsedBody([
            'email' => '  TEST@EXAMPLE.COM  ',
            'name' => '<script>alert("xss")</script>John Doe',
            'password' => 'password123'
        ]);
    
    $filteredRequest = $this->controller->filterRequest($request);
    $filteredData = $filteredRequest->getParsedBody();
    
    expect($filteredData['email'])->toBe('test@example.com');
    expect($filteredData['name'])->toBe('John Doe');
    
    // Test that validation passes on properly filtered data
    expect(fn() => $this->controller->validateRequest($filteredRequest))->not->toThrow();
});

test('validation catches errors appropriately', function () {
    $request = (new ServerRequest())
        ->withParsedBody([
            'email' => 'not-an-email',
            'name' => '',
            'password' => 'short'
        ]);
    
    expect(fn() => $this->controller->validateRequest($request))
        ->toThrow(ValidationFailedException::class);
});
```

### Integration Testing Complete Pipelines

Test the complete middleware pipeline to ensure everything works together:

```php
<?php
// tests/Feature/MiddlewarePipelineTest.php

beforeEach(function () {
    $this->container = createTestContainer();
    $this->app = $this->container->getApp(); // Includes all middleware
    
    // Register test controller
    $router = $this->container->getRouter();
    $router->post('users.create', '/api/users', function () {
        return new CreateUserController($this->container->getUserService());
    });
});

test('complete authentication and validation pipeline works together', function () {
    $requestData = [
        'email' => '  TEST@EXAMPLE.COM  ',
        'name' => '<b>John</b> Doe',
        'password' => 'password123'
    ];
    
    $request = (new ServerRequest([], [], '/api/users', 'POST'))
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Authorization', 'Bearer ' . createValidToken())
        ->withParsedBody($requestData);
    
    $response = $this->app->process($request);
    
    expect($response->getStatusCode())->toBe(201);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['success'])->toBeTrue();
    
    // Verify filtering worked (email lowercased, HTML stripped)
    expect($body['user']['email'])->toBe('test@example.com');
    expect($body['user']['name'])->toBe('John Doe');
});

test('pipeline rejects unauthenticated requests', function () {
    $request = (new ServerRequest([], [], '/api/users', 'POST'))
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody(['email' => 'test@example.com']);
    
    expect(fn() => $this->app->process($request))
        ->toThrow(AuthenticationException::class);
});

function createTestContainer(): ServiceContainer
{
    // Create test container with appropriate middleware configuration
    return new TestServiceContainer([
        'app.environment' => 'testing',
        'features.authentication' => true
    ]);
}

function createValidToken(): string
{
    return JWT::encode(['user_id' => 1], 'test-secret', 'HS256');
}
```

## Key Takeaways

1. **Choose the Right Layer**: Don't implement in middleware what infrastructure does better. Rate limiting belongs in nginx, not PHP.
2. **Leverage Middle's Built-in Interfaces**: Use `RequestValidatorInterface` and `RequestFilterInterface` with controllers for clean validation and filtering.
3. **Leverage External Libraries**: Use proven libraries like Symfony Validator and Firebase JWT rather than building everything from scratch.
4. **Composition is King**: Middle's explicit middleware composition makes complex request processing predictable and debuggable.
5. **Test at Multiple Levels**: Unit test individual middleware, integration test the complete pipeline.
6. **Design for Flexibility**: Use conditional middleware and environment-based composition to handle different deployment scenarios.

## Next Steps

With sophisticated middleware patterns mastered, you're ready to explore comprehensive testing strategies in Chapter 5. You'll learn how to test middleware pipelines, create reliable test doubles, and build test suites that give you confidence to refactor and extend your Middle applications.

The middleware patterns you've learned here handle the cross-cutting concerns that would otherwise scatter throughout your codebase. Middle's explicit composition approach means you always know exactly what's happening to each request - no hidden behaviors, no surprise side effects.

Go on with [Chapter 5](../chapter5/).