---
layout: default
title: "Middle Framework Guide"
description: "Complete guide to building maintainable web applications with explicit architecture"
nav_order: 1
permalink: /
---

# Middle Framework Guide

**A micro-framework built around one simple principle: everything should be explicit, replaceable, and safe to change.**

Middle takes a different approach to web application architecture. Instead of magic and conventions, it provides clear interfaces and explicit composition. You can use it to build your organization's perfect framework, not be forced to fit into someone else's choices.

## Why Middle?

- **🔍 No Magic, No Surprises** - Every dependency is explicit, every behavior is visible
- **🔧 Everything is Replaceable** - Interface-driven design lets you swap any component
- **🛡️ Safe to Change** - Clean boundaries prevent accidental coupling
- **🧪 Built for Testing** - Explicit dependencies make comprehensive testing straightforward

## Middle's Place in the PHP Ecosystem

Middle is designed for teams that prioritize long-term maintainability and architectural clarity over rapid prototyping. While many popular frameworks excel at getting applications to market quickly through conventions and automation, Middle optimizes for the phase that comes after - when your application succeeds, grows complex, and needs to be maintained by multiple developers over years. This focus extends to dependency management: Middle's minimal core means you consciously choose exactly which external libraries your application depends on, rather than inheriting a large dependency tree. We embrace "Proudly Found Elsewhere" - leveraging proven libraries like Symfony Router and Twig - but only the specific components your application actually needs.

## The Complete Guide

### Core Foundations
**[Chapter 1: Architecture Fundamentals](chapter1)**  
*Understanding Middle's Core Concepts*  
Learn the middleware pipeline, explicit dependency injection, and interface-driven design that form Middle's foundation.

**[Chapter 2: Getting Started](chapter2)**  
*From Installation to First Feature*  
Build your first Middle application with a complete task management API that demonstrates all core patterns.

**[Chapter 3: Interface Design Mastery](chapter3)**  
*Building Maintainable Contracts*  
Design interfaces that express business intent clearly and evolve gracefully as your application grows.

### Building Applications
**[Chapter 4: Middleware Deep Dive](chapter4)**  
*Building Custom Application Behavior*  
Create authentication, validation, caching, and other cross-cutting concerns using Middle's explicit composition.

**[Chapter 5: Secure by Design](chapter5)**  
*Defensive Programming and Architectural Security*  
Leverage PHP's process isolation and Middle's minimal attack surface for robust security. Learn defensive programming patterns, security-first interface design, and how explicit architecture prevents common vulnerabilities.

**[Chapter 6: Testing Strategies](chapter6)**  
*Confidence Through Comprehensive Coverage*  
Build test suites that leverage Middle's architecture for reliable unit and integration testing.

**[Chapter 7: Common Patterns and Solutions](chapter7)**  
*Practical Implementations*  
Implement authentication, data persistence, API responses, and external service integration using Middle's patterns.

### Scaling and Production
**[Chapter 8: Growing Your Application](chapter8)**  
*Scaling from Simple to Complex*  
Organize larger applications into modules with their own directory, service definitions and routes.

---

**Ready to start?** Begin with [Chapter 1: Architecture Fundamentals](chapter1) to understand Middle's core principles.

**Middle Framework: Explicit. Replaceable. Safe.**