---
title: "7. Common Patterns and Solutions"
description: "Implement authentication, data persistence, API responses, and external service integration using Middle's patterns."
layout: default
nav_order: 8
permalink: /chapter7/
---

# Chapter 7: Common Patterns and Solutions
*Practical Implementations with Middle's Architecture*

## Introduction

Every web application needs the same core functionality: authentication, data persistence, API responses, configuration management, and external service integration. The difference between frameworks lies not in what features they provide, but in how clearly you can understand, test, and modify those features.

This chapter demonstrates how to implement essential application patterns using Middle's explicit architecture. We'll cover:

**Domain Modeling:**
- Value objects that encapsulate validation and business meaning
- Rich entities with behavior, not just data containers
- Entity collections for type-safe object handling

**Data Persistence:**
- Repository patterns with UUID-based identifiers
- Domain-focused interfaces that express business needs

**Authentication & Sessions:**
- Session-based authentication using Middle's built-in session system
- Integration with Middle's request attributes for user context

**Output Formatting:**
- Views and renderers for JSON, HTML, and any output format
- Format-agnostic controllers that separate data from presentation

**External Integration:**
- HTTP client patterns for third-party services
- Interface-driven design for replaceable service implementations

Rather than providing complete implementations, we'll focus on the key design decisions and integration points that make these patterns maintainable and replaceable—the architectural choices that let you evolve your application confidently over time.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## Domain Entities and Value Objects

Before diving into specific patterns, let's establish how to represent business concepts in Middle applications using domain entities and value objects.

### Value Objects for Domain Primitives

Value objects encapsulate primitive types with domain meaning and validation, meaning you can be sure you are handling a valid value instead of just a string that might contain anything. They're not always necessary, but for things like e-mails that can only be valid in their exact formatting they are preferable to simple strings.

```php
<?php
// src/ValueObject/Email.php
namespace Middle\Skeleton\ValueObject;

class Email
{
    private string $value;
    
    public function __construct(string $email)
    {
        $email = trim($email);
        
        if (empty($email)) {
            throw new \InvalidArgumentException('Email address cannot be empty');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
        
        $this->value = strtolower($email);
    }
    
    public function toString(): string
    {
        return $this->value;
    }
    
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Entity Design Principles

Entities represent core business concepts with data, identity, and autonomous behavior:

```php
<?php
// composer require ramsey/uuid
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

// src/Entity/User.php
namespace Middle\Skeleton\Entity;

class User
{
    private function __construct(
        private ?UuidInterface $id,
        private Email $email,
        private string $name,
        private string $passwordHash,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}
    
    public static function create(string $email, string $name, string $password): self
    {
        return new self(
            null, // ID assigned when saved
            new Email($email),
            $name,
            password_hash($password, PASSWORD_DEFAULT),
            true,
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ? Uuid::fromBytes($data['id']) : null,
            new Email($data['email']),
            $data['name'],
            $data['password_hash'],
            (bool) $data['is_active'],
            new \DateTimeImmutable($data['created_at']),
            new \DateTimeImmutable($data['updated_at'])
        );
    }
    
    // Business methods, not just getters
    public function changePassword(string $newPassword): self
    {
        $clone = clone $this;
        $clone->passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }
    
    public function changeEmail(string $newEmail): self
    {
        $clone = clone $this;
        $clone->email = new Email($newEmail); // Validates automatically
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }
    
    public function deactivate(): self
    {
        $clone = clone $this;
        $clone->isActive = false;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }
    
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
    
    public function withId(UuidInterface $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }
    
    // Accessors
    public function getId(): ?UuidInterface { return $this->id; }
    public function getEmail(): Email { return $this->email; }
    public function getName(): string { return $this->name; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id?->toString(),
            'email' => $this->email->toString(),
            'name' => $this->name,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c')
        ];
    }
}
```

### Entity Collections

For handling multiple entities. Arrays cannot be typed as PHP has no generics (for now), to be sure a collection of entities is all of the right type you can encapsulate them in Collection objects that behave like arrays.

```php
<?php
// src/Entity/UserCollection.php
namespace Middle\Skeleton\Entity;

class UserCollection implements \Iterator, \Countable
{
    private array $users;
    private int $position = 0;
    
    public function __construct(array $users = [])
    {
        $this->users = array_values($users);
    }
    
    public function add(User $user): void
    {
        $this->users[] = $user;
    }
    
    /** @throws  \OutOfBoundsException when not found*/
    public function findByEmail(Email $email): User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail()->equals($email)) {
                return $user;
            }
        }
        throw \OutOfBoundsException('E-mail address not found: '.$email->toString());
    }
    
    public function activeUsers(): UserCollection
    {
        return new self(array_filter($this->users, fn($user) => $user->isActive()));
    }
    
    public function toArray(): array
    {
        return array_map(fn($user) => $user->toArray(), $this->users);
    }
    
    // Iterator implementation
    public function current(): User { return $this->users[$this->position]; }
    public function key(): int { return $this->position; }
    public function next(): void { ++$this->position; }
    public function rewind(): void { $this->position = 0; }
    public function valid(): bool { return isset($this->users[$this->position]); }
    
    // Countable implementation
    public function count(): int { return count($this->users); }
}
```

## Repository Patterns with UUIDs

Repository patterns abstract data access behind interfaces. For identifiers, we'll use UUIDs instead of auto-incrementing integers for several practical benefits: they prevent enumeration attacks where attackers can guess valid IDs by incrementing numbers, they eliminate accidental ID confusion between different entity types (using a User ID where an Account ID was expected), and they work naturally in distributed systems without coordination overhead. While UUIDs are larger than integers (16 bytes vs 4-8 bytes), when stored as binary the storage overhead is negligible compared to the rest of your data, and modern databases handle UUID performance with minimal impact.

*Note: as `ramsey/uuid` is pragmatically a standard in PHP we won't bother abstracting it away.*

### Domain-Focused Repository Interface

```php
<?php
// src/Repository/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    /** @throws  \OutOfBoundsException when not found */
    public function findById(UuidInterface $id): User;
    /** @throws  \OutOfBoundsException when not found */
    public function findByEmail(Email $email): User;
    public function save(User $user): User;
    public function findActiveUsers(): UserCollection;
    public function emailExists(Email $email): bool;
}
```

# Database Implementation

```php
<?php
// src/Repository/DatabaseUserRepository.php
class DatabaseUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $db) {}
    
    /** @throws  \OutOfBoundsException when not found */
    public function findById(UuidInterface $id): User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id->getBytes()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \OutOfBoundsException('User ID not found: '.$id->toString());
        }
        
        return User::fromArray($data);
    }
    
    /** @throws  \OutOfBoundsException when not found */
    public function findByEmail(Email $email): User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email->toString()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \OutOfBoundsException('User e-mail address not found: '.$email->toString());
        }
        
        return User::fromArray($data);
    }
    
    public function save(User $user): User
    {
        if (is_null($user->getId())) {
            return $this->insertUser($user);
        } else {
            return $this->updateUser($user);
        }
    }
    
    public function findActiveUsers(): UserCollection
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE is_active = 1 ORDER BY created_at DESC');
        $stmt->execute();
        
        $users = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::fromArray($data);
        }
        
        return new UserCollection($users);
    }
    
    public function emailExists(Email $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email->toString()]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function insertUser(User $user): User
    {
        $id = Uuid::uuid4();
        $now = new \DateTimeImmutable();
        
        $stmt = $this->db->prepare('
            INSERT INTO users (id, email, name, password_hash, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $id->getBytes(),
            $user->getEmail()->toString(),
            $user->getName(),
            $user->getPasswordHash(),
            $user->isActive() ? 1 : 0,
            $user->getCreatedAt()->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s')
        ]);
        
        return $user->withId($id);
    }
    
    private function updateUser(User $user): User
    {
        $now = new \DateTimeImmutable();
        
        $stmt = $this->db->prepare('
            UPDATE users 
            SET email = ?, name = ?, password_hash = ?, is_active = ?, updated_at = ?
            WHERE id = ?
        ');
        
        $stmt->execute([
            $user->getEmail()->toString(),
            $user->getName(),
            $user->getPasswordHash(),
            $user->isActive() ? 1 : 0,
            $now->format('Y-m-d H:i:s'),
            $user->getId()->getBytes()
        ]);
        
        return $user;
    }
}
```

## Authentication Using Middle's Session System

Middle provides built-in session handling through `SessionMiddleware` and `SessionInterface`. Let's implement authentication using these components. We already showed one example in [Chapter 4](https://jschreuder.github.io/Middle/chapter4/#authentication-middleware), in addition it could also be done using Sessions:

### Session-Based Authentication Using Middle's Session System

For traditional web applications, use Middle's built-in session handling:

```php
<?php
// src/Service/SessionAuthenticationService.php
class SessionAuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
    public function authenticate(string $email, string $password): AuthenticationResult
    {
        $emailObj = new Email($email); // Validates email format

        try {
            $user = $this->userRepository->findByEmail($emailObj);
        } catch (\OutOfBoundsException $e) {
            return AuthenticationResult::failure('Invalid credentials');
        }
        
        if (!$user->verifyPassword($password)) {
            return AuthenticationResult::failure('Invalid credentials');
        }
        
        if (!$user->isActive()) {
            return AuthenticationResult::failure('Account is inactive');
        }
        
        return AuthenticationResult::success($user, 'session-based');
    }
    
    public function loginUser(SessionInterface $session, User $user): void
    {
        $session->set('user_id', $user->getId()->toString());
        $session->set('email', $user->getEmail()->toString());
        $session->set('login_time', time());
        $session->rotateId(); // Prevent session fixation attacks
    }
    
    public function getCurrentUser(string $sessionId): ?User
    {
        // This method signature is for interface compatibility
        // In practice, we get the user from the session via middleware
        throw new \RuntimeException('Use getCurrentUserFromSession instead');
    }
    
    public function getCurrentUserFromSession(SessionInterface $session): ?User
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return null;
        }
        
        return $this->userRepository->findById(Uuid::fromString($userId));
    }
    
    public function logoutUser(SessionInterface $session): void
    {
        $session->destroy();
    }
    
    public function generateToken(User $user): string
    {
        return 'session-based'; // Not used for session auth
    }
}

// src/ServerMiddleware/SessionAuthenticationMiddleware.php
class SessionAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionAuthenticationService $authService,
        private array $publicRoutes = []
    ) {}
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isPublicRoute($request)) {
            return $handler->handle($request);
        }
        
        $session = $request->getAttribute('session');
        if (!$session instanceof SessionInterface) {
            throw new AuthenticationException('Session required');
        }
        
        $user = $this->authService->getCurrentUserFromSession($session);
        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }
        
        // Add user to request attributes, just like JWT middleware
        $authenticatedRequest = $request->withAttribute('authenticated_user', $user);
        return $handler->handle($authenticatedRequest);
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

### Application Stack Configuration

Configure authentication in your service container using Middle's session system:

```php
<?php
// In ServiceContainer.php
use jschreuder\Middle\ServerMiddleware\SessionMiddleware;
use jschreuder\Middle\Session\LaminasSessionProcessor;

public function getApp(): ApplicationStack
{
    $app = new ApplicationStack(new ControllerRunner());
    
    // Core request processing
    $app = $app->withMiddleware(new JsonRequestParserMiddleware());
    
    // Session-based authentication stack
    $app = $app->withMiddleware(new SessionMiddleware($this->getSessionProcessor()))
        ->withMiddleware(new SessionAuthenticationMiddleware(
            $this->getSessionAuthService(),
            ['/api/auth/.*', '/health', '/']
        ));
    
    $app = $app->withMiddleware(new RoutingMiddleware($this->getAppRouter(), $this->get404Handler()))
           ->withMiddleware(new ErrorHandlerMiddleware($this->getLogger(), $this->get500Handler()));
    
    return $app;
}

public function getSessionProcessor(): SessionProcessorInterface
{
    return new LaminasSessionProcessor($this->getLaminasSessionConfig());
}

public function getSessionAuthService(): SessionAuthenticationService
{
    return new SessionAuthenticationService($this->getUserRepository());
}
```

## Views & Renderers: Supporting JSON, HTML, Whatever You Need

Just as `TwigRenderer` takes View data and formats it as HTML using templates, you can create renderers that format the same data as JSON, XML, or any other format. The renderer pattern separates your business data from its presentation format:

```php
<?php
// src/View/JsonRenderer.php
class JsonRenderer implements RendererInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private array $transformers = []
    ) {}
    
    public function render(ServerRequestInterface $request, ViewInterface $view): ResponseInterface
    {
        $data = $this->transformData($view->getParameters());
        
        $response = $this->responseFactory
            ->createResponse($view->getStatusCode())
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        $response->getBody()->rewind();
        
        return $response;
    }
    
    private function transformData(array $data): array
    {
        $transformed = [];
        foreach ($data as $key => $value) {
            $transformed[$key] = $this->transformValue($value);
        }
        return $transformed;
    }
    
    private function transformValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'transformValue'], $value);
        }
        
        // Use registered transformers
        foreach ($this->transformers as $class => $transformer) {
            if ($value instanceof $class) {
                return $transformer->transform($value);
            }
        }
        
        // Fall back to toArray() method if available
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
        
        return $value;
    }
}

// src/Transformer/DataTransformerInterface.php
interface DataTransformerInterface
{
    public function transform(object $object): array;
}

// src/Transformer/UserApiTransformer.php
class UserApiTransformer implements DataTransformerInterface
{
    public function transform(object $user): array
    {
        assert($user instanceof User);
        
        return [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail()->toString(),
            'name' => $user->getName(),
            'active' => $user->isActive(),
            'joined_at' => $user->getCreatedAt()->format('c')
        ];
    }
}
```

### Format-Agnostic Controllers

Controllers can accept any renderer, making them format-agnostic. The same business logic works for HTML, JSON, or any other output format:

```php
<?php
class GetUserController implements ControllerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RendererInterface $renderer  // Could be Twig, JSON, XML, etc.
    ) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Uuid::fromString($request->getAttribute('id'));
        $user = $this->userRepository->findById($userId);
        
        // Same data, different output format based on renderer
        return $this->renderer->render($request, new View('user/profile.twig', [
            'user' => $user,
            'success' => true
        ]));
    }
}

// Or choose renderer based on request format
class FlexibleUserController implements ControllerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RendererInterface $htmlRenderer,
        private RendererInterface $jsonRenderer
    ) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->userRepository->findById(
            Uuid::fromString($request->getAttribute('id'))
        );
        
        $view = new View('user/profile.twig', ['user' => $user]);
        
        // Choose renderer based on Accept header or route
        $format = $request->getAttribute('format', 'html');
        $renderer = match($format) {
            'json' => $this->jsonRenderer,
            'html' => $this->htmlRenderer,
            default => $this->htmlRenderer
        };
        
        return $renderer->render($request, $view);
    }
}
```

### Service Container Setup

```php
<?php
// In ServiceContainer.php
public function getJsonRenderer(): RendererInterface
{
    $renderer = new JsonRenderer($this->getResponseFactory());
    $renderer->registerTransformer(User::class, new UserApiTransformer());
    return $renderer;
}

public function getHtmlRenderer(): RendererInterface
{
    return new TwigRenderer($this->getTwig(), $this->getResponseFactory());
}

// Controllers can be configured with specific renderers
public function getUserController(): GetUserController
{
    return new GetUserController(
        $this->getUserRepository(),
        $this->getJsonRenderer()  // or $this->getHtmlRenderer()
    );
}

// Or configured to handle multiple formats
public function getFlexibleUserController(): FlexibleUserController
{
    return new FlexibleUserController(
        $this->getUserRepository(),
        $this->getHtmlRenderer(),
        $this->getJsonRenderer()
    );
}
```

This approach maintains Middle's principles: whether you're templating HTML with Twig or formatting JSON with transformers, you're separating business data from presentation logic. The same View data can be rendered in multiple formats by swapping the renderer implementation.

## External Service Integration

Integrate external services through interfaces that hide implementation details and make services replaceable.

### HTTP Client Integration

Use Guzzle for HTTP communication while maintaining your interface:

```php
<?php
// composer require guzzlehttp/guzzle

// src/Service/HttpClientInterface.php
interface HttpClientInterface
{
    public function get(string $url, array $headers = []): HttpResponse;
    public function post(string $url, array $data = [], array $headers = []): HttpResponse;
}

class HttpResponse
{
    public function __construct(
        private int $statusCode,
        private string $body,
        private array $headers = []
    ) {}
    
    public function getStatusCode(): int { return $this->statusCode; }
    public function getBody(): string { return $this->body; }
    public function isSuccessful(): bool { return $this->statusCode >= 200 && $this->statusCode < 300; }
    
    public function getJsonData(): array
    {
        $data = json_decode($this->body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Response is not valid JSON');
        }
        return $data;
    }
}

// src/Service/GuzzleHttpClient.php
use GuzzleHttp\Client;

class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(
        private Client $client,
        private int $timeout = 30
    ) {}
    
    public function get(string $url, array $headers = []): HttpResponse
    {
        return $this->makeRequest('GET', $url, [], $headers);
    }
    
    public function post(string $url, array $data = [], array $headers = []): HttpResponse
    {
        return $this->makeRequest('POST', $url, $data, $headers);
    }
    
    private function makeRequest(string $method, string $url, array $data = [], array $headers = []): HttpResponse
    {
        try {
            $options = [
                'headers' => $headers,
                'timeout' => $this->timeout,
                'json' => $data
            ];
            
            $response = $this->client->request($method, $url, $options);
            
            return new HttpResponse(
                $response->getStatusCode(),
                $response->getBody()->getContents()
            );
            
        } catch (\Exception $e) {
            throw new ExternalServiceException("HTTP request failed: {$e->getMessage()}", 0, $e);
        }
    }
}
```

### Service Container Integration

Configure HTTP clients in your service container:

```php
<?php
// In ServiceContainer.php
public function getHttpClient(): HttpClientInterface
{
    return new GuzzleHttpClient(new Client(), 30);
}

public function getPaymentGateway(): PaymentGatewayInterface
{
    $provider = $this->config('payment.provider');
    
    return match($provider) {
        'stripe' => new StripePaymentGateway(
            $this->getHttpClient(),
            $this->config('payment.stripe.secret_key')
        ),
        default => throw new \InvalidArgumentException("Unknown payment provider: {$provider}")
    };
}
```

## Key Takeaways

1. **Use Middle's Built-in Features**: Leverage `SessionMiddleware` and interfaces instead of building authentication from scratch.
2. **UUID-Based Identifiers**: Use Ramsey's `UuidInterface` directly - it's already a well-designed standard interface that doesn't need additional abstraction.
3. **Value Objects for Domain Primitives**: Create value objects like `Email` that encapsulate validation and domain meaning around primitive types.
4. **Rich Entity Design**: Create domain entities with business behavior, not just data containers. Let them enforce their own invariants.
5. **Flexible Output Formatting**: Use the View/Renderer pattern to support multiple output formats from the same business logic.

## Next Steps

With these essential patterns mastered using Middle's actual architecture and best practices, you're ready to tackle application organization and growth strategies in Chapter 8. You'll learn how to structure larger Middle applications while maintaining the architectural clarity and explicit patterns demonstrated here.

The key insight is that Middle's strength lies in its explicit composition and interface-driven design. By building on the skeleton's patterns and using Middle's built-in features, you create applications that are both powerful and maintainable. These patterns scale naturally as your application grows, because every dependency remains visible and every component boundary stays clean.

Go on with [Chapter 8](../chapter8/).