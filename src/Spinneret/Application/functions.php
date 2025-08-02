<?php

namespace Arakne\Spinneret\Application;

use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Value\ClosureValue;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Value\TaggedServiceIterator;

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
 * @return ValueInterface
 */
function service_closure(string $id): ValueInterface
{
    return new ClosureValue(new Reference($id));
}
