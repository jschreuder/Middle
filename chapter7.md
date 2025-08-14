---
title: "7. Organizing Growing Applications"
description: "Organize larger applications into modules with their own directory, service definitions and routes."
layout: default
nav_order: 8
permalink: /chapter7/
---

# Chapter 7: Organizing Growing Applications
*Simple Structure for Sustainable Growth*

## Introduction

Your Middle application started simple: a few controllers, basic authentication, and straightforward business logic. But as your application succeeds, complexity inevitably grows. More features, more team members, more files to navigate.

This chapter shows you how to organize growing Middle applications into modules using three simple techniques that scale with your codebase while preserving Middle's architectural clarity. These can be contained in a single repository, but also devided up into multiple repositories. As long as your autoloader knows where to find them.

1. **Filesystem organization** with PSR-4 namespaces
2. **Service container traits** grouped by domain
3. **Routing providers** for related endpoints

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## Filesystem Organization with PSR-4

### Growing Beyond Single Directories

As your application grows, organizing by domain makes navigation and maintenance easier. Instead of dumping everything into `src/Controller/` and `src/Service/`, create domain-focused namespaces:

```
src/
├── User/                    # User management domain
│   ├── Controller/
│   │   ├── RegisterUserController.php
│   │   ├── LoginController.php
│   │   └── ProfileController.php
│   ├── Service/
│   │   ├── UserRegistrationService.php
│   │   └── UserAuthenticationService.php
│   ├── Repository/
│   │   └── DatabaseUserRepository.php
│   ├── Entity/
│   │   └── User.php
│   └── ValueObject/
│       └── Email.php
├── Product/                 # Product catalog domain
│   ├── Controller/
│   │   ├── ListProductsController.php
│   │   └── ProductDetailsController.php
│   ├── Service/
│   │   └── ProductCatalogService.php
│   ├── Repository/
│   │   └── DatabaseProductRepository.php
│   └── Entity/
│       └── Product.php
├── Order/                   # Order processing domain
│   ├── Controller/
│   │   ├── CreateOrderController.php
│   │   └── OrderStatusController.php
│   ├── Service/
│   │   └── OrderProcessingService.php
│   └── Entity/
│       └── Order.php
└── Core/                    # Main entrypoints, core application
│   ├── Controller/
│   │   └── IndexController.php
└── Shared/                  # Cross-cutting concerns
    ├── Exception/
    │   └── ValidationException.php
    └── Infrastructure/
        └── DatabaseConnection.php
```

### Composer PSR-4 Configuration

Update your `composer.json` to support domain namespaces:

```json
{
    "autoload": {
        "psr-4": {
            "Middle\\Skeleton\\": "src/",
            "Middle\\Skeleton\\User\\": "src/User/",
            "Middle\\Skeleton\\Product\\": "src/Product/",
            "Middle\\Skeleton\\Order\\": "src/Order/",
            "Middle\\Skeleton\\Shared\\": "src/Shared/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

After updating composer.json:
```bash
composer dump-autoload
```

Note that you can also put these in completely different directories that are not under `src/` and configure those. All that's needed is to tell your autoloader where to find them according to the [PSR-4 standard](https://www.php-fig.org/psr/psr-4/).

### Testing External Modules

When modules come from external repositories (vendor directories, separate repos):

- **Test modules in isolation** within their own repositories
- **Test integration points** in your main application
- **Avoid duplicating unit tests** for external module internals

This keeps testing responsibilities clear and avoids coupling your application tests to external module implementations.

## Service Container Organization with Traits

### Grouping Services by Domain

As your service container grows, organize it using traits grouped by domain concern:

```php
<?php
// src/ServiceContainer/UserServicesTrait.php
namespace Middle\Skeleton\ServiceContainer;

use Middle\Skeleton\User\Repository\DatabaseUserRepository;
use Middle\Skeleton\User\Repository\UserRepositoryInterface;
use Middle\Skeleton\User\Service\UserRegistrationService;
use Middle\Skeleton\User\Service\UserAuthenticationService;

trait UserServicesTrait
{
    public function getUserRepository(): UserRepositoryInterface
    {
        return $this->userRepository ??= new DatabaseUserRepository(
            $this->getDatabase(),
            $this->getLogger()
        );
    }
    
    public function getUserRegistrationService(): UserRegistrationService
    {
        return $this->userRegistrationService ??= new UserRegistrationService(
            $this->getUserRepository()
        );
    }
    
    public function getUserAuthenticationService(): UserAuthenticationService
    {
        return $this->userAuthenticationService ??= new UserAuthenticationService(
            $this->getUserRepository(),
            $this->config('auth.jwt.secret')
        );
    }
}

// src/ServiceContainer/ProductServicesTrait.php
namespace Middle\Skeleton\ServiceContainer;

use Middle\Skeleton\Product\Repository\DatabaseProductRepository;
use Middle\Skeleton\Product\Repository\ProductRepositoryInterface;
use Middle\Skeleton\Product\Service\ProductCatalogService;

trait ProductServicesTrait
{
    public function getProductRepository(): ProductRepositoryInterface
    {
        return $this->productRepository ??= new DatabaseProductRepository(
            $this->getDatabase()
        );
    }
    
    public function getProductCatalogService(): ProductCatalogService
    {
        return $this->productCatalogService ??= new ProductCatalogService(
            $this->getProductRepository(),
            $this->getCacheStore()
        );
    }
}
```

These examples are in the same namespace as the initial ServiceContainer, but there's no reason they have to be. They can also be put somewhere completely different as the previous paragraph showed for PSR-4 namespaces.

### Main Service Container Composition

```php
<?php
// src/ServiceContainer.php
namespace Middle\Skeleton;

use jschreuder\MiddleDi\ConfigTrait;
use Middle\Skeleton\ServiceContainer\UserServicesTrait;
use Middle\Skeleton\ServiceContainer\ProductServicesTrait;
use Middle\Skeleton\ServiceContainer\OrderServicesTrait;

class ServiceContainer
{
    use ConfigTrait;
    use UserServicesTrait;
    use ProductServicesTrait;
    use OrderServicesTrait;
    
    // Core application services
    public function getApp(): ApplicationStack
    {
        $app = new ApplicationStack(new ControllerRunner());
        
        // Add core middleware
        $app = $app->withMiddleware(new JsonRequestParserMiddleware())
                   ->withMiddleware(new RoutingMiddleware($this->getAppRouter(), $this->get404Handler()));
        
        // Add authentication for protected routes
        if ($this->config('features.authentication', true)) {
            $app = $app->withMiddleware(new JwtAuthenticationMiddleware(
                $this->getUserAuthenticationService(),
                ['/api/auth/.*', '/health']
            ));
        }
        
        // Error handling as outermost layer
        return $app->withMiddleware(new ErrorHandlerMiddleware(
            $this->getLogger(),
            $this->get500Handler()
        ));
    }
    
    // Shared infrastructure services
    public function getDatabase(): PDO
    {
        return $this->database ??= new PDO(
            $this->config('db.dsn') . ';dbname=' . $this->config('db.dbname'),
            $this->config('db.user'),
            $this->config('db.pass'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    
    // etc.
}
```

## Routing Providers for Related Endpoints

### Domain-Specific Routing

Create routing providers to group related endpoints together:

```php
<?php
// src/User/UserRoutingProvider.php
namespace Middle\Skeleton\User;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Middle\Skeleton\ServiceContainer;
use Middle\Skeleton\User\Controller\RegisterUserController;
use Middle\Skeleton\User\Controller\LoginController;

class UserRoutingProvider implements RoutingProviderInterface
{
    public function __construct(private ServiceContainer $container) {}
    
    public function registerRoutes(RouterInterface $router): void
    {
        // Authentication routes
        $router->post('auth.register', '/api/auth/register', function () {
            return new RegisterUserController(
                $this->container->getUserRegistrationService()
            );
        });
        
        $router->post('auth.login', '/api/auth/login', function () {
            return new LoginController(
                $this->container->getUserAuthenticationService()
            );
        });

        // etc.
    }
}

// src/Product/ProductRoutingProvider.php
namespace Middle\Skeleton\Product;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Middle\Skeleton\Product\Controller\ListProductsController;
use Middle\Skeleton\Product\Controller\ProductDetailsController;

class ProductRoutingProvider implements RoutingProviderInterface
{
    public function __construct(private ServiceContainer $container) {}
    
    public function registerRoutes(RouterInterface $router): void
    {
        // Public product routes
        $router->get('products.list', '/api/products', function () {
            return new ListProductsController(
                $this->container->getProductCatalogService()
            );
        });
        
        $router->get('products.detail', '/api/products/{id}', function () {
            return new ProductDetailsController(
                $this->container->getProductRepository()
            );
        });

        // etc.
    }
}
```

### Main Routing Configuration

```php
<?php
// src/GeneralRoutingProvider.php - Updated to use domain providers
namespace Middle\Skeleton;

use jschreuder\Middle\Router\RouterInterface;
use jschreuder\Middle\Router\RoutingProviderInterface;
use Middle\Skeleton\Core\Controller\IndexController;
use Middle\Skeleton\Order\OrderRoutingProvider;
use Middle\Skeleton\Product\ProductRoutingProvider;
use Middle\Skeleton\User\UserRoutingProvider;

class GeneralRoutingProvider implements RoutingProviderInterface
{
    public function __construct(private ServiceContainer $container) {}
    
    public function registerRoutes(RouterInterface $router): void
    {
        // Register domain-specific routes
        (new UserRoutingProvider($this->container))->registerRoutes($router);
        (new ProductRoutingProvider($this->container))->registerRoutes($router);
        (new OrderRoutingProvider($this->container))->registerRoutes($router);

        // Add other routes
        $router->get('index', '/', function () {
            return new IndexController();
        });

        // etc.
    }
}
```

## Benefits of This Approach

### 1. **Preserves Middle's Simplicity**
- Create the module system you need, Middle provides the tools
- Clear, explicit service creation and routing
- Easy to understand and debug

### 2. **Maintains Architectural Clarity**
- All dependencies remain explicit
- Service container shows exactly what gets created
- Routing providers make endpoint organization visible

### 3. **Easy Navigation**
- Related code lives together
- Consistent directory structure across domains
- Clear namespace organization

### 4. **Testing Remains Straightforward**
- Domain-specific test organization
- Easy to mock domain services in isolation
- Feature tests verify cross-domain integration

## Common Patterns

### Shared Infrastructure

Some services span multiple domains. Keep these in a `Shared` or `Core` namespace:

```php
<?php
// src/Shared/Infrastructure/CacheServicesTrait.php
trait CacheServicesTrait
{
    public function getCacheStore(): CacheInterface
    {
        return match($this->config('cache.driver')) {
            'redis' => new RedisCache($this->getRedisConnection()),
            'file' => new FilesystemCache($this->config('cache.path')),
            'null' => new NullCache(),
            default => throw new \InvalidArgumentException('Unknown cache driver')
        };
    }
}
```

### Cross-Domain Communication

When domains need to communicate, use explicit service injection rather than events:

```php
<?php
// src/Order/Service/OrderProcessingService.php
class OrderProcessingService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository, // Cross-domain dependency
        private EmailServiceInterface $emailService
    ) {}
    
    public function processOrder(OrderData $orderData): Order
    {
        // Validate products exist
        foreach ($orderData->items as $item) {
            if (!$this->productRepository->exists($item->productId)) {
                throw new ValidationException(['product' => 'Product not found']);
            }
        }
        
        $order = Order::create($orderData);
        $savedOrder = $this->orderRepository->save($order);
        
        // Send confirmation email
        $this->emailService->sendOrderConfirmation($savedOrder);
        
        return $savedOrder;
    }
}
```

## Key Takeaways

1. **Use Filesystem Organization**: Group related code by domain using PSR-4 namespaces
2. **Organize Service Container**: Use traits to group domain-specific services
3. **Create Routing Providers**: Group related endpoints together for better maintainability
4. **Keep It Simple**: Avoid complex automatic-magic module systems - Middle's explicitness is its strength
5. **Test Domain by Domain**: Organize tests to match your source structure

## Next Steps

With your Middle application properly organized, you're ready to deploy and maintain it in production. Chapter 8 covers deployment strategies, monitoring, and long-term maintenance approaches that leverage Middle's explicit architecture for reliable operations.

The organizational patterns you've learned here scale from small teams to large organizations while preserving the architectural clarity that makes Middle applications maintainable. Remember: Middle's strength lies in explicitness and simplicity - resist the urge to add complex layers that hide what your application is actually doing.

Go on with [Chapter 8](../chapter8/).