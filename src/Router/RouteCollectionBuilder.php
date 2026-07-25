<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Decorates a {@see RouteCollection} to provide a fluent interface for building routes to be used by the router.
 *
 * All added routes will provide attributes:
 * - _target: the target request class name
 * - _fields_extractor: the fields extractor class name
 *
 * The inner {@see RouteCollection} can be accessed via the `routes` property.
 * Do not use it directly, unless you know what you are doing.
 *
 * @todo allow add "constant" request (i.e. without field extractor nor form).
 */
final readonly class RouteCollectionBuilder
{
    public RouteCollection $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * Define a new route
     *
     * @param string $path The path pattern to match
     * @param class-string $target The target request class name
     * @param list<string> $methods List of allowed HTTP methods. Should be uppercase. If not provided all methods are allowed.
     * @param class-string|null $fieldsExtractor The fields extractor class name. The class name must take the target class name constructor argument, and be callable, taking the request as first argument.
     *
     * @return $this
     */
    public function add(string $path, string $target, array $methods = [], ?string $fieldsExtractor = null): self
    {
        $attributes = [
            '_target' => $target,
            '_fields_extractor' => $fieldsExtractor ?? FieldsExtractor::class,
        ];

        $this->routes->add($target, new Route($path, $attributes, methods: $methods));

        return $this;
    }

    /**
     * Define a new route with GET method
     *
     * @param string $path The path pattern to match
     * @param class-string $target The target request class name
     * @param class-string|null $fieldsExtractor The fields extractor class name. The class name must take the target class name constructor argument, and be callable, taking the request as first argument.
     *
     * @return $this
     */
    public function get(string $path, string $target, ?string $fieldsExtractor = null): self
    {
        return $this->add($path, $target, ['GET'], $fieldsExtractor);
    }

    /**
     * Define a new route with POST method
     *
     * @param string $path The path pattern to match
     * @param class-string $target The target request class name
     * @param class-string|null $fieldsExtractor The fields extractor class name. The class name must take the target class name constructor argument, and be callable, taking the request as first argument.
     *
     * @return $this
     */
    public function post(string $path, string $target, ?string $fieldsExtractor = null): self
    {
        return $this->add($path, $target, ['POST'], $fieldsExtractor);
    }
}
