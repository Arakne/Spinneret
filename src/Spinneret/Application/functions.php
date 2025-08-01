<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Container\Argument\ArgumentInterface;
use Arakne\Spinneret\Container\Argument\ClosureArgument;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Argument\TaggedServiceIterator;

/**
 * Helper function to create a new service reference.
 *
 * @param string $id The service identifier
 * @return Reference
 */
function service(string $id): Reference
{
    return new Reference($id);
}

/**
 * Helper function to create a new service reference, which can be null if the service is not found.
 *
 * @param string $id The service identifier
 * @return Reference
 */
function service_nullable(string $id): Reference
{
    return new Reference($id, nullOnInvalid: true);
}

/**
 * Inject to service parameter an iterable of services with a specific tag.
 *
 * @param string $tag The tag name
 * @return TaggedServiceIterator
 */
function tagged_services(string $tag): TaggedServiceIterator
{
    return new TaggedServiceIterator($tag);
}

/**
 * Helper function to create a service resolver closure.
 *
 * @param string $id The service identifier
 * @return ArgumentInterface
 */
function service_closure(string $id): ArgumentInterface
{
    return new ClosureArgument(new Reference($id));
}
