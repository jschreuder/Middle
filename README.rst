================
Middle Framework
================

.. image:: https://scrutinizer-ci.com/g/jschreuder/Middle/badges/quality-score.png?b=master
   :target: https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master
   :alt: Scrutinizer Code Quality
.. image:: https://scrutinizer-ci.com/g/jschreuder/Middle/badges/coverage.png?b=master
   :target: https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master
   :alt: Scrutinizer Build Status
.. image:: https://scrutinizer-ci.com/g/jschreuder/Middle/badges/build.png?b=master
   :target: https://scrutinizer-ci.com/g/jschreuder/Middle/?branch=master
   :alt: Scrutinizer Build Status

A micro-framework build around the idea of middlewares, basicly: MIDDLEWARE ALL
THE THINGS. What does that mean? Everything is based around simple interfaces
for which the default implementation can can be either replaced or decorated.
The implementations can be atomic in nature: just performing one task. Composing
complex capabilities by choosing which simple middlewares you decorate or add
to the stack. Also every component is NIH; PSR-1, PSR-2, PSR-3, PSR-4, PSR-7,
PSR-15 and PSR-17; as of version 2.0 aimed at PHP 8.0.

Check out the `Middle skeleton <https://github.com/jschreuder/Middle-skeleton>`_
application to get an example setup running quickly.

*Note: all examples use Laminas Diactoros, but any PSR-7 compatible library will
work as well.*

----------------------------
Running a Middle application
----------------------------

The heart of an application build with Middle is the
``ApplicationStackInterface`` which just takes a PSR ``ServerRequestInterface``
and must return a ``ResponseInterface``. Running it, after having set it up,
will look as follows (using Laminas Diactoros as PSR-7 implementation):

.. code-block:: php

    <?php
    // Create Request
    $request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();

    // Render the response by processing the request
    $response = $app->process($request);

    // And output it
    (new Laminas\Diactoros\Response\SapiEmitter())->emit($response);

---------------------
Minimal default setup
---------------------

The following sets up the application with routing middleware and a controller
runner at its heart. The middlewares are processed in LIFO (last in, first out)
order.

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // Let's setup a router which needs the base-URL, and a handler for
    // generating a response when no route is matched.
    $router = new Middle\Router\SymfonyRouter('http://localhost');
    $fallbackController = Middle\Controller\CallableController::fromCallable(
        function () {
            return new Laminas\Diactoros\Response\JsonResponse(['error'], 400);
        }
    );

    // Setup the application with controller runner and the routing middleware
    $app = new Middle\ApplicationStack(
        new Middle\Controller\ControllerRunner(),
        new Middle\ServerMiddleware\RoutingMiddleware($router, $fallbackController)
    );

With that setup we can now add some routes (using the ``$router`` from above):

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // Using the convenience method for GET request on '/'
    $router->get('home', '/',
        Middle\Controller\CallableController::factoryFromCallable(function () {
            return new Laminas\Diactoros\Response\JsonResponse([
                'message' => 'Welcome to our homepage',
            ]);
        })
    );

The included routing depends on Symfony's Routing component. In the path you
can use the variable notation. The ``get()`` method also supports 2 additional
arguments: the ``$defaults`` array and the ``$requirements`` array which are
passed on to the Symfony routing.

---------------------------------
Add more middlewares to the stack
---------------------------------

The example below builds an application using 2 additional middlewares that add
sessions & error handling on top of the previous example.

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // starting with the example above, let's add these before running the app.

    // Now let's also make sessions available on the request
    $app = $app->withMiddleware(
        new Middle\ServerMiddleware\SessionMiddleware(
            new Middle\Session\LaminasSessionProcessor()
        )
    );

    // And finally: make sure any errors are caught
    $app = $app->withMiddleware(
        new Middle\ServerMiddleware\ErrorHandlerMiddleware(
            new Monolog\Logger(...),
            function (Psr\Http\Message\ServerRequestInterface $request, \Throwable $exception) {
                return new Laminas\Diactoros\Response\JsonResponse(['error'], 500);
            }
        )
    );

The session middleware adds a ``'session'`` attribute to the ServerRequest's
attributes, which contains an instance of
``jschreuder\Middle\Session\SessionInterface``.

The error handler takes a PSR-3 ``LoggerInterface`` instance to which it will
log any uncaught Exceptions as ``alert``. The callable in the constructor will
be called directly after that and is expected to return a ``ResponseInterface``
that shows an error to the user.

--------------------
Also with templating
--------------------

There's also a build-in generic templating solution. To use it the Controller
can create an intermediate ``ViewInterface`` instance and take a
``RendererInterface`` instance as well to render it into a Response object.

The example below uses the included Twig renderer:

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // Setup the renderer for Twig with a Twig_Environment instance and a
    // PSR-17 Response factory for generating the Response object
    $renderer = new Middle\View\TwigRenderer(
        new Twig\Environment(...),
        $responseFactory
    );

    $router->get('home', '/',
        Middle\Controller\CallableViewController::factoryFromCallable(
            function (Psr\Http\Message\ServerRequestInterface $request) use ($renderer) {
                // Should render template.twig and parameters with Twig and return
                // response with status code 200
                return $renderer->render($request, new Middle\View\View('template.twig', [
                    'view' => 'parameters',
                ], 200));
            }
        );
    );

The ``RendererInterface`` can be decorated. It you'd like to also use a view to
return a redirect, you can decorate the renderer like this:

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // Decorate with the RedirectRendererMiddleware which needs a PSR-17
    // Response factory for generating the Response object
    $renderer = new Middle\View\RedirectRendererMiddleware(
        $renderer,
        $responseFactory
    );

Once you've done that you can create redirects like this:

.. code-block:: php

    <?php
    use jschreuder\Middle;
    $router->get('redirect.example', '/redirect/to/home',
        Middle\Controller\CallableViewController::factoryFromCallable(
            function (Psr\Http\Message\ServerRequestInterface $request) use ($renderer) {
                // This will redirect to the path '/' with status 302, the status is
                // optional and will default to 302 when omitted.
                return $renderer->render($request, new Middle\View\RedirectView('/', 302));
            }
        );
    );

------------------------------------------------
Middlewares and a Dependency Injection Container
------------------------------------------------

I'll use Pimple in the example below, but the same concept can probably be used
in other containers as well:

.. code-block:: php

    <?php
    use jschreuder\Middle;
    // First create the central app object in the container
    $container = Pimple\Container();
    $container['app'] = function () {
        return new Middle\ApplicationStack(
            new Middle\Controller\ControllerRunner()
        );
    };

    // Now to add a middleware you can do this
    $container->extend('app',
        function (Middle\ApplicationStack $app, Pimple\Container $container) {
            return $app->withMiddleware(
                new Middle\ServerMiddleware\RoutingMiddleware(
                    $container['router'], $container['fallbackHandler']
                )
            );
        }
    );

When doing this through in multiple places, for example through service
providers, the order might be less explicit, so be extra mindful of the order
in which you add the middlewares.

-----------------
Included services
-----------------

There's a few services included that all have their default implementations
and may be replaced or decorated as you wish:

* ``SessionProcessorInterface`` with its default option depending on
  ``laminas/laminas-session``. It allows for setting & getting values, 
  destroying the session or rotating its ID. The ``LaminasSessionProcessor``
  can be loaded through the ``SessionMiddleware`` as shown above.

* ``RouterInterface`` with its default depending on Symfony Routing component.
  It is loaded through the ``RoutingMiddleware`` as shown above. It has methods
  for adding the commonly used HTTP methods, parsing a request and getting its
  URL generator to facilitate reverse routing. Related interfaces are the
  ``RouteMatchInterface``, the ``UrlGeneratorInterface`` and the
  ``RoutingProviderInterface``.

* ``RendererInterface`` with its default depending on Twig to render templates
  as shown above. You could also wrap it in other Middlewares for additional
  parsing or replace it completely. The related ``ViewInterface`` is expected
  to be given and have the information necessary to render a template.

----------------------
Questions with answers
----------------------

1. *Another micro-framework... why?*
   I created an application using Silex, but it got in my way. Also I prefer
   PSR-7 over Symfony's implementation. I started refactoring it out and
   replaced it with just its Routing component, Twig, and Laminas's Diactoros 
   and Session libraries. After a while I realised I created a microframework 
   in its own right and extracted it from my application.

2. *Why are all classes final?*
   The intend is to follow the SOLID `Open/Closed principle
   <https://en.wikipedia.org/wiki/Open/closed_principle>`_. This says to be
   open for extension but closed for modification. Every dependency is
   type-hinted as an interface, and not against any concrete implementation.
   All classes can be extended with middlewares, either like the
   ApplicationStack or by using the `Decorator pattern
   <https://en.wikipedia.org/wiki/Decorator_pattern>`_. Thus you can extend or
   replace any class, but not modify how they work internally. As such only
   the interfaces are part of this framework's API.

3. *Do I have to use Twig, Symfony's router or Laminas's Session library?*
   No, but there are only some batteries included. The ones provided are
   implemented using those packages. You can replace those pretty easily by
   implementing the Routing or Session interfaces using another library.
