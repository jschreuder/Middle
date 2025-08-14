---
title: "Chapter 2"
description: "Getting Started"
layout: default
nav_order: 3
permalink: /chapter2/
---

# Chapter 2: Getting Started
*From Installation to First Feature*

## Introduction

The best way to understand Middle Framework is to build something with it. This chapter gets you from zero to a working application quickly, then walks through implementing a simple feature that demonstrates Middle's core patterns: explicit dependencies, interface design, middleware composition, and comprehensive testing.

We'll build a simple task management API that shows how Middle's explicit architecture makes application behavior predictable and testable. By the end, you'll have hands-on experience with Middle's development patterns and understand why explicit architecture leads to more maintainable applications.

*Note: We recommend using `declare(strict_types = 1);` everywhere, but for brevity we did not include them in the code examples.*

## Installation and Quick Start

### Getting Started with the Middle Skeleton

The Middle Skeleton is your foundation for new projects. It demonstrates all of Middle's architectural patterns in a working application:

```bash
# Create a new project using the skeleton
composer create-project jschreuder/middle-skeleton my-task-app dev-master
cd my-task-app

# Set up the environment
chmod 0755 var/logs

# Configure environment files
cp config/env.php.dist config/env.php
cp config/dev.php.dist config/dev.php

# Test the skeleton is working
./console middle:example "Your Name"
# Output: Hello world! to you Your Name

# Start the development server
./console middle:webserver
# Server running at http://localhost:8080

# Test the web endpoint
curl http://localhost:8080
# Output: {"message":"Hello world!"}

# Run the test suite
./vendor/bin/pest
# All tests should pass
```

### What the Skeleton Provides

The skeleton gives you a complete foundation with Middle's best practices already in place:

**Core Architecture:**
- Service container with explicit dependency injection using Middle-DI
- Middleware pipeline with error handling and JSON parsing
- Router integration with Symfony Router
- Comprehensive testing setup with Pest PHP

**Development Tools:**
- Console application framework for CLI commands
- Web server for development
- Logging with Monolog
- Environment-based configuration

**Directory Structure:**
```
my-task-app/
├── config/                 # Environment-specific configuration
│   ├── app_init.php        # Application bootstrapping
│   ├── env.php             # Environment selection
│   └── dev.php             # Development configuration
├── src/                    # Application source code
│   ├── Command/            # Console commands
│   ├── Controller/         # HTTP request handlers
│   ├── ServiceContainer.php # Dependency injection container
│   └── GeneralRoutingProvider.php # Route definitions
├── tests/                  # Test suites with Pest PHP
│   ├── Feature/            # Integration tests
│   └── Unit/               # Isolated unit tests
├── web/                    # Web server document root
│   └── index.php           # HTTP entry point
└── console                 # CLI entry point
```

### Environment Configuration

The skeleton includes template configuration files that you copy and customize for your environment. Configure your development environment by editing `config/dev.php`:

```php
<?php
return [
    'site.url' => 'http://localhost:8080',
    
    'logger.name' => 'task-app',
    'logger.path' => __DIR__ . '/../var/logs/dev-' . date('Ymd') . '.log',
    
    // Database configuration (we'll add this later)
    'db.dsn' => 'sqlite:' . __DIR__ . '/../var/database.sqlite',
    'db.user' => '',
    'db.pass' => '',
];
```

The `config/env.php` file controls which environment configuration is loaded:

```php
<?php
// config/env.php
return 'dev'; // or 'prod', 'test', etc.
```

## Understanding the Skeleton Architecture

### Request Flow setup

Let's trace how a request flows through the skeleton application, using Laminas' PSR-7 implementation.

```php
<?php
// web/index.php - HTTP entry point

// 1. Bootstrap the application
// This file loads the autoloader, configures PHP's local environment, loads 
// the configuration and service container and returns the container.
$container = require __DIR__ . '/../config/app_init.php';

// 2. Get the configured application stack
$app = $container->getApp();

// 3. Register routes
// If you have multiple entry points, each can have their own router
(new \jschreuder\Middle\Router\RoutingProviderCollection(
    new \Middle\Skeleton\GeneralRoutingProvider($container)
))->registerRoutes($container->getAppRouter());

// 4. Process the request through middleware pipeline
$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();
$response = $app->process($request);

// 5. Send response to browser
(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
```

### The Service Container

The skeleton's service container demonstrates Middle's explicit dependency injection using [Middle DI](https://github.com/jschreuder/Middle-di) - a compile-time dependency injection container that provides full IDE support with zero runtime overhead in production. It was already mostly introduced in Chapter 1, here's a quick reminder of what it looks like and two lines to add we'll need later:

```php
<?php
// src/ServiceContainer.php

namespace Middle\Skeleton;

use jschreuder\Middle\ServerMiddleware\RequestValidatorMiddleware; // You need to add this line

class ServiceContainer
{
    use ConfigTrait;
    
    public function getApp(): ApplicationStack
    {
        return new ApplicationStack(
            new ControllerRunner(),
            new RequestValidatorMiddleware(), // You need to add this line
            new JsonRequestParserMiddleware(),
            new RoutingMiddleware(
                $this->getAppRouter(), 
                $this->get404Handler()
            ),
            new ErrorHandlerMiddleware(
                $this->getLogger(), 
                $this->get500Handler()
            )
        );
    }
    
    public function getExample(): ExampleService { /* .. */ }
    
    public function getLogger(): LoggerInterface { /* .. */ }

    // etc.
}
```

## Building Your First Feature: Task Management API

Let's implement a simple task management feature that demonstrates Middle's core patterns. We'll create endpoints to list, create, and complete tasks.

### Step 0: Rename the Application Namespace

First, let's rename the skeleton namespace to something more appropriate for our application:

**Update composer.json autoload section:**
```json
{
    "autoload": {
        "psr-4": {
            "Example\\TaskManager\\": "src/"
        }
    }
}
```

**Update existing files to use the new namespace:**

Update files `config/app_init.php`, `console`, `web/index.php` and all in `src/` and `tests/` to change `Middle\Skeleton` to `Example\TaskManager`. Both their namespace definitions, docblock references and any use statements referring to the old namespace.

**Then regenerate the autoloader:**
```bash
composer dump-autoload
```

**Verify the change works:**
```bash
./console middle:example "Test" # Should still work
```

Now you have a proper application namespace that you can build upon.

### Step 1: Define the Domain

Start with a simple task entity that encapsulates business logic:

```php
<?php
// src/Entity/Task.php

namespace Example\TaskManager\Entity;

class Task
{
    public function __construct(
        private ?int $id,
        private string $title,
        private string $description,
        private bool $completed = false,
        private ?\DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['title'],
            $data['description'],
            (bool) $data['completed'],
            new \DateTimeImmutable($data['created_at'])
        );
    }
    
    public function complete(): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->description,
            true,
            $this->createdAt
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'completed' => $this->completed,
            'created_at' => $this->createdAt->format('c')
        ];
    }
    
    // Accessors
    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function isCompleted(): bool { return $this->completed; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
```

*Note: We recommend using UUIDs instead of integer IDs using `ramsey/uuid` from Packagist. But to keep this example simple we've used integers here.*

### Step 2: Design the Repository Interface

Repositories take your entities in to or out of the data storage, most often a database but it could be anything (filesystem, memory, etc). Define what operations your application needs, not what your database provides:

```php
<?php
// src/Repository/TaskRepositoryInterface.php

namespace Example\TaskManager\Repository;

use Example\TaskManager\Entity\Task;

interface TaskRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): Task;
    public function save(Task $task): Task;
    public function delete(int $id): void;
}
```

### Step 3: Implement Database Repository

Create a simple PDO-based repository implementation:

```php
<?php
// src/Repository/DatabaseTaskRepository.php

namespace Example\TaskManager\Repository;

use Example\TaskManager\Entity\Task;
use PDO;

class DatabaseTaskRepository implements TaskRepositoryInterface
{
    public function __construct(private PDO $db) {}
    
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM tasks ORDER BY created_at DESC');
        $tasks = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = Task::fromArray($row);
        }
        
        return $tasks;
    }
    
    public function findById(int $id): Task
    {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \OutOfBoundsException('No such Task ID: '.$id);
        }
        
        return Task::fromArray($data);
    }
    
    public function save(Task $task): Task
    {
        if ($task->getId() === null) {
            return $this->insert($task);
        } else {
            return $this->update($task);
        }
    }
    
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
    }
    
    private function insert(Task $task): Task
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks (title, description, completed, created_at) VALUES (?, ?, ?, ?)'
        );
        
        $stmt->execute([
            $task->getTitle(),
            $task->getDescription(),
            $task->isCompleted() ? 1 : 0,
            $task->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
        
        $id = (int) $this->db->lastInsertId();
        return new Task($id, $task->getTitle(), $task->getDescription(), $task->isCompleted(), $task->getCreatedAt());
    }
    
    private function update(Task $task): Task
    {
        $stmt = $this->db->prepare(
            'UPDATE tasks SET title = ?, description = ?, completed = ? WHERE id = ?'
        );
        
        $stmt->execute([
            $task->getTitle(),
            $task->getDescription(),
            $task->isCompleted() ? 1 : 0,
            $task->getId()
        ]);
        
        return $task;
    }
}
```

### Step 4: Create Controllers

Build controllers that handle HTTP requests and use your business logic. They should not have any state (no properties with changing values), only services for them to use while processing requests. Controllers are meant to translate HTTP requests to business logic calls your services understand.

*Note: In most cases you will need some more business logic between the controller and the repository, for those you should create service classes that do these additional things. In the example below we keep things simple and have the controller call the repository directly, but this would only suffice for very simple setups.*

```php
<?php
// src/Controller/ListTasksController.php

namespace Example\TaskManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Example\TaskManager\Entity\Task;
use Example\TaskManager\Repository\TaskRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListTasksController implements ControllerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $tasks = $this->taskRepository->findAll();
        
        return new JsonResponse([
            'success' => true,
            'tasks' => array_map(fn(Task $task) => $task->toArray(), $tasks)
        ]);
    }
}

// src/Controller/CreateTaskController.php

namespace Example\TaskManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use jschreuder\Middle\Controller\RequestValidatorInterface;
use jschreuder\Middle\Exception\ValidationFailedException;
use Laminas\Diactoros\Response\JsonResponse;
use Example\TaskManager\Entity\Task;
use Example\TaskManager\Repository\TaskRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateTaskController implements ControllerInterface, RequestValidatorInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}
    
    public function validateRequest(ServerRequestInterface $request): void
    {
        // Note: For production applications, we recommend using RequestFilterInterface 
        // for input filtering and validation libraries like Symfony Validator.
        // This simple validation is kept minimal for demonstration purposes.
        
        $data = $request->getParsedBody() ?? [];
        $errors = [];
        
        if (empty($data['title'] ?? '')) {
            $errors['title'] = 'Title is required';
        }
        
        if (empty($data['description'] ?? '')) {
            $errors['description'] = 'Description is required';
        }
        
        if (!empty($errors)) {
            throw new ValidationFailedException($errors);
        }
    }
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $task = new Task(null, $data['title'], $data['description']);
        $savedTask = $this->taskRepository->save($task);
        
        return new JsonResponse([
            'success' => true,
            'task' => $savedTask->toArray()
        ], 201);
    }
}

// src/Controller/CompleteTaskController.php

namespace Example\TaskManager\Controller;

use jschreuder\Middle\Controller\ControllerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Example\TaskManager\Repository\TaskRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CompleteTaskController implements ControllerInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}
    
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $task = $this->taskRepository->findById((int) $request->getAttribute('id'));
        } catch (\OutOfBoundsException $error) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        $completedTask = $task->complete();
        $savedTask = $this->taskRepository->save($completedTask);
        
        return new JsonResponse([
            'success' => true,
            'task' => $savedTask->toArray()
        ]);
    }
}
```

### Step 5: Wire Services in Container

Update the service container to provide your new services:

```php
<?php
// src/ServiceContainer.php - Add these methods

// Repository service
public function getTaskRepository(): TaskRepositoryInterface
{
    return new DatabaseTaskRepository($this->getDb());
}

// Controller services
public function getListTasksController(): ListTasksController
{
    return new ListTasksController($this->getTaskRepository());
}

public function getCreateTaskController(): CreateTaskController
{
    return new CreateTaskController($this->getTaskRepository());
}

public function getCompleteTaskController(): CompleteTaskController
{
    return new CompleteTaskController($this->getTaskRepository());
}

// Database service
public function getDb(): PDO
{
    $pdo = new PDO($this->config('db.dsn'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
```

### Step 6: Add Routes

Register your controllers with the router:

```php
<?php
// src/GeneralRoutingProvider.php - Add to registerRoutes method

public function registerRoutes(RouterInterface $router): void
{
    // Existing home route
    $router->get('home', '/', function () {
        return new ExampleController($this->container->getExample());
    });
    
    // Task management routes
    $router->get('tasks.list', '/tasks', function () {
        return $this->container->getListTasksController();
    });
    
    $router->post('tasks.create', '/tasks', function () {
        return $this->container->getCreateTaskController();
    });
    
    $router->put('tasks.complete', '/tasks/{id}/complete', function () {
        return $this->container->getCompleteTaskController();
    });
}
```

### Step 7: Create Database Migration
The skeleton includes Phinx for database migrations. Let's create a migration for our tasks table:

```bash
# Create a new migration
./vendor/bin/phinx create CreateTasksTable
# This creates a file like: migrations/20231201123456_create_tasks_table.php
```

Edit the generated migration file:

```php
<?php
// migrations/20250814123456_create_tasks_table.php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTasksTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tasks');
        $table->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('description', 'text')
              ->addColumn('completed', 'boolean', ['default' => false])
              ->addColumn('created_at', 'datetime')
              ->create();
    }
}
```

Run the migration:

```bash
# Run migrations to create the database tables
./vendor/bin/phinx migrate

# Check migration status
./vendor/bin/phinx status
```

The `phinx.php` configuration file included with the skeleton automatically uses your Middle configuration, so it will create the database using the same settings as your application.

## Testing Your Feature

The architecture Middle promotes makes testing straightforward. Let's write comprehensive tests using Pest PHP.

### Unit Tests

Test individual components in isolation:

```php
<?php
// tests/Unit/Entity/TaskTest.php

use Example\TaskManager\Entity\Task;

test('task can be created with title and description', function () {
    $task = new Task(null, 'Learn Middle Framework', 'Read documentation and build examples');
    
    expect($task->getTitle())->toBe('Learn Middle Framework');
    expect($task->getDescription())->toBe('Read documentation and build examples');
    expect($task->isCompleted())->toBeFalse();
    expect($task->getId())->toBeNull();
});

test('task can be completed', function () {
    $task = new Task(null, 'Test Task', 'Description');
    $completedTask = $task->complete();
    
    expect($completedTask->isCompleted())->toBeTrue();
    expect($completedTask->getTitle())->toBe($task->getTitle());
});

test('task can be converted to array', function () {
    $task = new Task(1, 'Test Task', 'Test Description');
    $array = $task->toArray();
    
    expect($array)->toHaveKey('id', 1);
    expect($array)->toHaveKey('title', 'Test Task');
    expect($array)->toHaveKey('completed', false);
});
```

```php
<?php
// tests/Unit/Controller/CreateTaskControllerTest.php

use Example\TaskManager\Controller\CreateTaskController;
use Example\TaskManager\Repository\TaskRepositoryInterface;
use Example\TaskManager\Entity\Task;
use Laminas\Diactoros\ServerRequest;
use jschreuder\Middle\Exception\ValidationFailedException;

beforeEach(function () {
    $this->taskRepository = mock(TaskRepositoryInterface::class);
    $this->controller = new CreateTaskController($this->taskRepository);
});

test('creates task successfully', function () {
    $taskData = ['title' => 'Test Task', 'description' => 'Test Description'];
    $task = new Task(1, $taskData['title'], $taskData['description']);
    
    $this->taskRepository->shouldReceive('save')
        ->once()
        ->andReturn($task);
    
    $request = (new ServerRequest())
        ->withParsedBody($taskData);
    
    $response = $this->controller->execute($request);
    
    expect($response->getStatusCode())->toBe(201);
    
    $body = json_decode((string) $response->getBody(), true);
    expect($body['success'])->toBeTrue();
    expect($body['task']['title'])->toBe('Test Task');
});

test('validates required fields', function () {
    $request = (new ServerRequest())
        ->withParsedBody(['title' => '']);
    
    expect(fn() => $this->controller->validateRequest($request))
        ->toThrow(ValidationFailedException::class);
});
```

### Integration Tests

Test complete request flows:

```php
<?php
// tests/Feature/TaskManagementTest.php

use Example\TaskManager\ServiceContainer;
use Example\TaskManager\GeneralRoutingProvider;
use Laminas\Diactoros\ServerRequest;

beforeEach(function () {
    // Setup application with in-memory database
    $this->container = new ServiceContainer([
        'db.dsn' => 'sqlite::memory:'
    ]);
    
    // Create database schema
    $this->createTestDatabase();
    
    $this->app = $this->container->getApp();
    
    // Register routes
    (new \jschreuder\Middle\Router\RoutingProviderCollection(
        new GeneralRoutingProvider($this->container)
    ))->registerRoutes($this->container->getAppRouter());
});

test('can create and list tasks', function () {
    // Create a task
    $createRequest = (new ServerRequest([], [], '/tasks', 'POST'))
        ->withHeader('Content-Type', 'application/json')
        ->withParsedBody([
            'title' => 'Test Task',
            'description' => 'Test Description'
        ]);
    
    $createResponse = $this->app->process($createRequest);
    expect($createResponse->getStatusCode())->toBe(201);
    
    // List tasks
    $listRequest = new ServerRequest([], [], '/tasks', 'GET');
    $listResponse = $this->app->process($listRequest);
    
    expect($listResponse->getStatusCode())->toBe(200);
    
    $body = json_decode((string) $listResponse->getBody(), true);
    expect($body['success'])->toBeTrue();
    expect($body['tasks'])->toHaveCount(1);
    expect($body['tasks'][0]['title'])->toBe('Test Task');
});

function createTestDatabase(): void
{
    // Get the in-memory database from the container and create schema
    $pdo = $this->container->getDb();
    $pdo->exec('
        CREATE TABLE tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            completed INTEGER DEFAULT 0,
            created_at TEXT NOT NULL
        )
    ');
}
```

### Run Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test types
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature

# Run with coverage
./vendor/bin/pest --coverage
```

## Testing Your API Manually

### Start the Application

```bash
./console middle:webserver
```

### Test the Endpoints

```bash
# List tasks (initially empty)
curl http://localhost:8080/tasks

# Create a task
curl -X POST http://localhost:8080/tasks \
  -H "Content-Type: application/json" \
  -d '{"title": "Learn Middle Framework", "description": "Build a task management API"}'

# List tasks again
curl http://localhost:8080/tasks

# Complete a task
curl -X PUT http://localhost:8080/tasks/1/complete

# Verify task is completed
curl http://localhost:8080/tasks
```

Expected responses:
```json
// GET /tasks
{
  "success": true,
  "tasks": [
    {
      "id": 1,
      "title": "Learn Middle Framework",
      "description": "Build a task management API",
      "completed": false,
      "created_at": "2023-12-01T10:30:00+00:00"
    }
  ]
}

// POST /tasks
{
  "success": true,
  "task": {
    "id": 1,
    "title": "Learn Middle Framework",
    "description": "Build a task management API",
    "completed": false,
    "created_at": "2023-12-01T10:30:00+00:00"
  }
}
```

## What You've Built

In this chapter, you've created a complete task management API that demonstrates Middle's core principles:

**Explicit Architecture:**
- Every dependency is visible in the service container
- Request flow through middleware is clear and traceable
- No hidden framework magic or auto-wiring

**Interface-Driven Design:**
- Business logic depends on `TaskRepositoryInterface`, not implementation
- Easy to swap database for in-memory storage for testing
- Controllers use interfaces, making them testable in isolation

**Domain-Focused Design:**
- `Task` entity encapsulates business logic and validation
- Repository interface expresses application needs, not database structure
- Controllers handle HTTP concerns, business logic stays in domain objects

**Comprehensive Testing:**
- Unit tests verify individual component behavior
- Integration tests verify complete request flows
- Pest PHP makes tests readable and maintainable

## Middle's Development Benefits

Building this feature demonstrates why Middle's explicit approach is valuable:

**Predictable Behavior:**
- You can trace exactly what happens to each request
- Middle-DI compile-time optimizations are transparent and predictable
- Configuration explicitly controls application behavior

**Easy Testing:**
- Mock specific interfaces without complex setup
- Test business logic separate from HTTP concerns
- No framework internals to work around

**Safe Refactoring:**
- Interfaces prevent accidental coupling
- IDE support helps catch breaking changes
- Clear dependency graph makes impact analysis straightforward

**Team Collaboration:**
- Code is self-documenting through explicit dependencies
- New team members can understand request flow by reading code
- No hidden conventions to learn

## Next Steps

You now have hands-on experience with Middle's development patterns. In Chapter 3, we'll dive deeper into interface design - the foundation that makes Middle applications maintainable as they grow in complexity.

These patterns scale from simple APIs to complex applications while maintaining Middle's core promise: everything remains explicit, replaceable, and safe to change. Middle-DI's compile-time optimizations enhance performance while preserving the architectural clarity that makes Middle applications maintainable.