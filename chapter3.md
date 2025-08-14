---
title: "Chapter 3"
description: "Interface Design Mastery"
layout: default
nav_order: 4
permalink: /chapter3/
---

# Chapter 3: Interface Design Mastery
*Building Maintainable Contracts*

## Introduction

In Chapter 2, you experienced firsthand how `UserRepositoryInterface` allowed you to swap between database and in-memory implementations without changing a single line of controller code. This is the power of interface-driven design - it creates stable contracts that express business intent clearly and evolve gracefully over time.

But designing good interfaces requires understanding what makes them maintainable. Poor interface design leads to tight coupling, frequent breaking changes, and components that are difficult to test. Great interface design creates the foundation for Middle's explicit architecture to truly shine.

This chapter teaches you to design interfaces that will serve your application well as it grows from a simple prototype to a complex system maintained by multiple teams.

**The key insight:** Middle's explicit dependencies make interface design both more important and more rewarding than in frameworks with hidden coupling.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*  
*Note 2: We recommend using UUIDs instead of integer IDs using `ramsey/uuid` from Packagist. This package is assumed when we use the `UuidInterface` in examples.*

## The Interface Design Process

### Starting from Use Cases, Not Implementations

The best interfaces emerge from understanding what your application needs to accomplish, not from what your chosen libraries happen to provide. Let's walk through the process using a practical example.

**Scenario:** You're building an inventory management system for an e-commerce platform.

### Step 1: Gather Business Use Cases

Start by listing concrete use cases from your domain:

```php
<?php
/**
 * Inventory Management Use Cases:
 * 
 * 1. Check if a product is in stock
 * 2. Reserve items when customer adds to cart
 * 3. Release reserved items if cart expires
 * 4. Commit reserved items when order is placed
 * 5. Restock items when new inventory arrives
 * 6. Get low-stock alerts for purchasing team
 */
```

### Step 2: Group Related Operations

Look for natural groupings based on business workflows:

```php
<?php
// Stock checking and display (read-only operations)
interface ProductStockQueryInterface
{
    public function getAvailableQuantity(UuidInterface $productId): int;
    public function isInStock(UuidInterface $productId, int $quantity = 1): bool;
    public function getLowStockProducts(int $threshold = 10): ProductCollection;
}

// Inventory reservations (cart operations)
interface InventoryReservationInterface  
{
    public function reserveItems(UuidInterface $productId, int $quantity, UuidInterface $customerId): UuidInterface;
    public function releaseReservation(UuidInterface $reservationId): void;
    public function commitReservation(UuidInterface $reservationId): void;
}

// Stock management (admin operations)
interface StockManagementInterface
{
    public function addStock(UuidInterface $productId, int $quantity, string $reason): void;
    public function adjustStock(UuidInterface $productId, int $newQuantity, string $reason): void;
    public function getStockHistory(UuidInterface $productId): StockHistoryCollection;
}
```

**Why Three Interfaces Instead of One?**
- **Different Consumers**: Queries for product pages, reservations for shopping cart, management for admin tools
- **Different Change Rates**: Query methods rarely change, reservation logic evolves with business rules
- **Different Security**: Queries are public, reservations need customer auth, management needs admin privileges

### Step 3: Design for Your Domain, Not Your Database

**❌ Database-Driven Design:**
```php
<?php
// Exposes database implementation details
interface ProductRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findByColumns(array $where): array;
    public function insert(array $data): int;
    public function executeQuery(string $sql, array $params): array;
}

// Problems: Exposes SQL (also possible security issue), returns arrays instead
// of entities, generic operations
```

**✅ Domain-Driven Design:**
```php
<?php
// Expresses business concepts
interface ProductCatalogInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findProduct(UuidInterface $productId): Product;
    public function findProductsByCategory(UuidInterface $categoryId): ProductCollection;
    public function searchProducts(SearchCriteria $criteria): ProductCollection;
    public function findFeaturedProducts(): ProductCollection;
}

// Benefits: Domain language, rich objects, business use cases
```

## Interface Sizing: Finding the Sweet Spot

### The Right Size for Maintainability

Each interface should have one reason to change. Here's how to recognize when an interface is appropriately sized:

**Too Small - Over-Fragmentation:**
```php
<?php
// Too many tiny interfaces create dependency bloat
interface UserFinderInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findById(UuidInterface $id): User;
}

interface UserSaverInterface
{
    public function save(User $user): void;
}

// Problem: Artificial boundaries, too many dependencies
```

**Too Large - God Interface:**
```php
<?php
// Monolithic interface with too many responsibilities
interface UserManagementInterface
{
    // User CRUD
    public function createUser(UserData $data): User;
    public function updateUser(UuidInterface $id, UserData $data): User;
    
    // Authentication  
    public function authenticateUser(string $email, string $password): ?User;
    
    // Email
    public function sendWelcomeEmail(User $user): void;
    
    // Reporting
    public function getUserStatistics(): UserStats;
}

// Problem: Changes for many reasons, hard to mock, forces many dependencies 
// and mixes concerns
```

**Just Right - Cohesive Interface:**
```php
<?php
// Related operations grouped by business purpose
interface UserRepositoryInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findById(UuidInterface $id): User;
    /** @throws \OutOfBoundsException when email doesn't exist */
    public function findByEmail(string $email): User;
    public function save(User $user): User;
    public function exists(UuidInterface $id): bool;
}

// Separate interface for different concern
interface UserAuthenticationInterface
{
    public function authenticateByCredentials(string $email, string $password): ?User;
    public function authenticateByToken(string $token): ?User;
    public function generateToken(User $user): AuthToken;
}

// Benefits: Single responsibility, easy to mock, clear purpose
```

## Key Interface Examples

Let's examine three essential interface patterns that appear in most Middle applications:

### Example 1: Repository Pattern

The repository pattern abstracts data access behind domain-focused operations:

```php
<?php
interface UserRepositoryInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findById(UuidInterface $id): User;
    /** @throws \OutOfBoundsException when email doesn't exist */
    public function findByEmail(string $email): User;
    public function save(User $user): User;
    public function findActiveUsers(): UserCollection;
    public function emailExists(string $email): bool;
}

// Domain-focused implementation
class DatabaseUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $db) {}
    
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findByEmail(string $email): User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \OutOfBoundsException('No such e-mail address: '.$email);
        }
        
        return User::fromArray($data);
    }
    
    // ... other methods
}

// Testing implementation
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    
    public function findByEmail(string $email): User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        throw new \OutOfBoundsException('No such e-mail address: '.$email);
    }
    
    // ... other methods
}
```

### Example 2: Service Interface

Service interfaces encapsulate business operations. Controllers should only contain logic to connect HTTP requests to the business logic, the business logic itself should be in Services. This keeps controllers small and allows complexity that is well managed behind maintainable abstractions.

```php
<?php
interface EmailServiceInterface
{
    public function sendWelcomeEmail(User $user): EmailResult;
    public function sendPasswordResetEmail(User $user, ResetToken $token): EmailResult;
    public function sendOrderConfirmation(Order $order): EmailResult;
}

class EmailResult
{
    private function __construct(
        private bool $success,
        private ?UuidInterface $messageId = null,
        private ?string $error = null
    ) {}
    
    public static function success(UuidInterface $messageId): self
    {
        return new self(true, $messageId);
    }
    
    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
    
    public function isSuccessful(): bool { return $this->success; }
    public function getMessageId(): ?UuidInterface { return $this->messageId; }
    public function getError(): ?string { return $this->error; }
}

// Multiple implementations based on environment
class SmtpEmailService implements EmailServiceInterface
{
    public function sendWelcomeEmail(User $user): EmailResult
    {
        // Send via SMTP
    }
}

class LogEmailService implements EmailServiceInterface
{
    public function sendWelcomeEmail(User $user): EmailResult
    {
        // Just for demonstration, this should of course create a Message and 
        // use its ID instead of a randomly generated one
        file_put_contents('/tmp/emails.log', "Welcome email to {$user->getEmail()}\n", FILE_APPEND);
        return EmailResult::success(Uuid::uuid4());
    }
}
```

### Example 3: Authentication Interface

Authentication interfaces separate concerns clearly:

```php
<?php
interface AuthenticationServiceInterface
{
    public function authenticate(string $email, string $password): AuthenticationResult;
    public function getCurrentUser(string $token): ?User;
    public function generateToken(User $user): string;
    public function invalidateToken(string $token): void;
}

class AuthenticationResult
{
    private function __construct(
        private bool $success,
        private ?User $user = null,
        private ?string $token = null,
        private ?string $error = null
    ) {}
    
    public static function success(User $user, string $token): self
    {
        return new self(true, $user, $token);
    }
    
    public static function failure(string $error): self
    {
        return new self(false, null, null, $error);
    }
    
    // Accessors...
}

// JWT implementation using external library
class JwtAuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private string $secretKey
    ) {}
    
    public function authenticate(string $email, string $password): AuthenticationResult
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !$user->verifyPassword($password)) {
            return AuthenticationResult::failure('Invalid credentials');
        }
        
        $token = $this->generateToken($user);
        return AuthenticationResult::success($user, $token);
    }
    
    // ... other methods using Firebase JWT library
}
```

## Common Interface Anti-Patterns

### Anti-Pattern 1: Leaky Abstractions

**❌ Bad - Database Details Leak Through:**
```php
<?php
interface UserRepositoryInterface
{
    public function query(string $sql, array $params): array;
    public function beginTransaction(): void;
    public function getLastInsertId(): int;
}

// Problems: SQL exposure (also possible security issue), can't implement with
// non-database storage
```

**✅ Good - Domain-Focused:**
```php
<?php
interface UserRepositoryInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findById(UuidInterface $id): User;
    public function save(User $user): User;
}

interface TransactionManagerInterface
{
    public function executeInTransaction(callable $operation): mixed;
}

// Benefits: No storage details, can be implemented by any persistence layer
```

### Anti-Pattern 2: Anemic Interfaces

**❌ Bad - Just CRUD Operations:**
```php
<?php
interface OrderRepositoryInterface
{
    public function create(array $data): int;
    public function read(int $id): ?array;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

// Problem: Doesn't express business operations
```

**✅ Good - Rich Domain Operations:**
```php
<?php
interface OrderRepositoryInterface
{
    /** @throws \OutOfBoundsException when ID doesn't exist */
    public function findById(UuidInterface $id): Order;
    public function findPendingOrders(UuidInterface $customerId): OrderCollection;
    public function save(Order $order): Order;
}

// Domain objects encapsulate business logic
class Order
{
    public function ship(TrackingNumber $trackingNumber): self
    {
        if ($this->status !== OrderStatus::PROCESSING) {
            throw new InvalidOrderStateException('Order must be processing to ship');
        }
        
        return $this->withStatus(OrderStatus::SHIPPED)
                   ->withTrackingNumber($trackingNumber);
    }
}
```

## Interface Testing in Middle

Explicit dependencies on interfaces makes testing their users straightforward:

### Mock-Based Testing

```php
<?php
describe('UserRegistrationService', function () {
    beforeEach(function () {
        $this->userRepository = mock(UserRepositoryInterface::class);
        $this->emailService = mock(EmailServiceInterface::class);
        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->emailService
        );
    });
    
    test('registers user and sends welcome email', function () {
        $email = 'john@example.com';
        $name = 'John Doe';
        $password = 'securepassword123';
        
        $this->userRepository->shouldReceive('emailExists')
            ->with($email)
            ->andReturn(false);
        
        $this->userRepository->shouldReceive('save')
            ->once()
            ->andReturn($user);
        
        $this->emailService->shouldReceive('sendWelcomeEmail')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn(EmailResult::success(Uuid::uuid4()));
        
        $result = $this->service->registerUser($email, $name, $password);
        
        expect($result)->toBeInstanceOf(User::class);
    });
});
```

## Best Practices for Middle Interfaces

### Design for Your Domain

```php
<?php
// ✅ Good - Express business operations
interface OrderFulfillmentInterface
{
    public function fulfillOrder(UuidInterface $orderId): FulfillmentResult;
    public function trackShipment(UuidInterface $shipmentId): TrackingInfo;
    public function handleReturn(ReturnRequest $request): ReturnResult;
}

// ❌ Bad - Generic database operations  
interface DatabaseOrderInterface
{
    public function selectOrderById(UuidInterface $id): array;
    public function updateOrderStatus(UuidInterface $id, string $status): bool;
}
```

### Use Rich Types

```php
<?php
// ✅ Good - Rich domain types
interface PaymentProcessorInterface
{
    public function processPayment(PaymentRequest $request): PaymentResult;
}

class PaymentRequest
{
    public function __construct(
        public readonly Money $amount,
        public readonly PaymentMethod $paymentMethod,
        public readonly UuidInterface $customerId
    ) {}
}

// ❌ Bad - Primitive obsession
interface PaymentProcessorInterface
{
    public function processPayment(float $amount, string $currency, array $cardData): array;
}
```

### Group Cohesively

```php
<?php
// ✅ Good - Related operations
interface ShoppingCartInterface
{
    public function addItem(UuidInterface $productId, int $quantity): void;
    public function removeItem(UuidInterface $productId): void;
    public function getItems(): CartItemCollection;
    public function getTotalPrice(): Money;
    public function clear(): void;
}

// ❌ Bad - Unrelated operations mixed
interface ShoppingInterface
{
    public function addToCart(UuidInterface $productId, int $quantity): void;
    public function processPayment(PaymentData $payment): PaymentResult;
    public function sendEmail(string $template, User $user): void;
}
```

## Key Takeaways

1. **Start with Use Cases**: Design interfaces based on what your application needs to accomplish, not what your libraries provide
2. **Express Business Intent**: Interface methods should read like business operations, not technical implementations
3. **Size Appropriately**: Group related operations that change together, avoid both god interfaces and excessive fragmentation
4. **Plan for Evolution**: Design interfaces that can grow through additive changes
5. **Leverage Middle's Strengths**: Explicit dependencies make interface testing straightforward and reliable

## Next Steps

With solid interface design skills, you're ready to build sophisticated middleware in Chapter 4. You'll learn how to implement authentication, validation, caching, and other cross-cutting concerns that complement your well-designed interfaces.

Remember: **Interfaces are the foundation of Middle's architecture**. Time spent designing good interfaces pays dividends throughout the lifetime of your application, enabling the explicit, replaceable, and maintainable code that makes Middle applications a joy to work with.

Go on with [Chapter 4](../chapter4/).