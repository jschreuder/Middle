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
Also every component is NIH; PSR-1, PSR-2, PSR-3, PSR-4, PSR-7 and inspired by
the proposed PSR-15; and PHP 7.0 or higher (probably 7.1+ once that's released).

The reason I prefer to use middleware approach is because it is far more
explicit in how the application works than (for example) event-based
programming. When using events, the order in which the handlers are executed
can get messy and very hard to debug. With decoration you'll still have to be
mindful of the order in which you add Middlewares (as some may depend on
others). But you'll always have a useful backtrace telling you where things
went wrong.

----------------------------
Running a Middle application
----------------------------

The heart of an application build with Middle is the ``ApplicationInterface``
which just takes a PSR ``ServerRequestInterface`` and returns a
``ResponseInterface``. Running it, after having set it up, will look as
follows (using Zend Diactoros as PSR-7 implementation):

.. code-block:: php

    <?php
    // Create Request
    $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();

    // Render the response
    $response = $app->process($request);

    // And output it
    (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);

---------------------
Minimal default setup
---------------------

The following sets up the application with routing middleware and a controller
runner at its heart:

.. code-block:: php

    <?php
    // This can only lookup request attribute 'controller' and execute it to
    // generate the Response
    $app = new jschreuder\Middle\ApplicationStack([
        new jschreuder\Middle\ControllerRunner()
    ]);

    // Let's add support for routing, which should add that controller
    // attribute. This also add a router which needs the base-URL, and a
    // handler for generating a response when no route is matched.
    $router = new jschreuder\Middle\Router\SymfonyRouter('http://localhost');
    $app = $app->withMiddleware(
        new jschreuder\Middle\Router\RoutingMiddleware(
            $router,
            function () {
                return new Zend\Diactoros\Response\JsonResponse(['error'], 400);
            }
        )
    );

With that setup we can now add some routes (using the ``$router`` from above):

.. code-block:: php

    <?php
    // Using the convenience method for GET request on '/'
    $router->get('home', '/', function () {
        return new Zend\Diactoros\Response\JsonResponse([
            'message' => 'Welcome to our homepage',
        ]);
    });

The included routing depends on Symfony's Routing component. In the path you
can use the variable notation. The ``get()`` method also supports 2 additional
arguments: the ``$defaults`` array and the ``$requirements`` array which are
passed on to the Symfony routing.

-----------------------------
Decorating the app a bit more
-----------------------------

The example below builds an application using 2 additional middlewares that add
sessions & error handling on top of the previous example.

.. code-block:: php

    <?php
    // starting with the example above, let's add these before running the app.

    // Now let's also make sessions available on the request
    $app = $app->withMiddleware(
        new jschreuder\Middle\Session\ZendSession(7200)
    );

    // And finally: make sure any errors are caught
    $app = $app->withMiddleware(
        new jschreuder\Middle\ErrorHandlerMiddleware(
            new Monolog\Logger(...),
            function (ServerRequestInterface $request, \Throwable $exception) {
                return new Zend\Diactoros\Response\JsonResponse(['error'], 500);
            }
        )
    );

The session middleware adds a ``'session'`` attribute to the ServerRequest's
attributes, which contains an instance of
``jschreuder\Middle\Session\SessionInterface``.

The error handler takes a PSR-3 ``LogerInterface`` instance to which it will
log any uncaught Exceptions as ``alert``. The callable in the constructor will
be called directly after that and is expected to return a ``ResponseInterface``
that shows an error to the user.

--------------------
Also with templating
--------------------

The central ``ApplicationInterface`` object, the ``ControllerRunner`` also
supports rendering templates into ``ResponseInterface`` objects. To do that the
Controller must return a ``ViewInterface`` instance and the ControllerRunner
must be build with a ``RendererInterface`` instance.

The example below uses the included Twig renderer:

.. code-block:: php

    <?php
    // Setup the renderer for Twig
    $renderer = new jschreuder\Middle\View\TwigRenderer(
        new \Twig_Environment(...)
    );

    // Now start with the ControllerRunner given the renderer:
    $app = new jschreuder\Middle\ApplicationStack([
        new jschreuder\Middle\ControllerRunner($renderer),
        new jschreuder\Middle\Router\RoutingMiddleware(
            $app, $router, function () { ... }
        ),
    ]);

    $router->get('home', '/', function () {
        // Should render template.twig and parameters with Twig and return
        // response with status code 200
        return new jschreuder\Middle\View\View('template.twig', [
            'view' => 'parameters',
        ], 200);
    });

The ``RendererInterface`` can also be decorated. It you'd like to also use a
view to return a redirect, you can decorate the renderer like this before
using it to construct the ControllerRunner:

.. code-block:: php

    <?php
    $renderer = new jschreuder\Middle\View\TwigRenderer(
        new \Twig_Environment(...)
    );
    $renderer = new jschreuder\Middle\View\RedirectRendererMiddleware(
        $renderer
    );

Once you've done that you can create redirects like this:

.. code-block:: php

    <?php
    $router->get('redirect.example', '/redirect/to/home', function () {
        // This will redirect to the path '/' with status 302, the status is
        // optional and will default to 302 when omitted.
        return new jschreuder\Middle\View\RedirectView('/', 302);
    });

------------------------------------------------
Middlewares and a Dependency Injection Container
------------------------------------------------

I'll use Pimple in the example below, but the same concept can probably be used
in other containers as well:

.. code-block:: php

    <?php
    // First create the central app object in the container
    $container = Pimple\Container();
    $container['app'] = new jschreuder\Middle\ApplicationStack([
        new jschreuder\Middle\ControllerRunner()
    ]);

    // Now to add a middleware you can do this
    $container->extend('app',
        function (jschreuder\Middle\ApplicationStack $app, Pimple\Container $container) {
            return $app->withMiddleware(
                new jschreuder\Middle\Router\RoutingMiddleware(
                    $container['router'], $container['fallbackHandler']
                )
            );
        }
    );

When doing this the order might be less explicit, so be extra mindful of the
order in which you add the middlewares.

-----------------
Included services
-----------------

There's a few services included that all have their default implementations
and may be replaced or decorated as you wish:

* ``SessionInterface`` with its default depending on Zend-Session. It allows
  for setting & getting values, destroying the session or rotating its ID. It
  can be loaded using the ``LoadZendSessionMiddleware``.

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
