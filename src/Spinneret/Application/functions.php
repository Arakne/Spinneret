<?php

namespace Arakne\Spinneret\Application;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

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
    return new Reference($id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
}

/**
 * Inject to service parameter an iterable of services with a specific tag.
 *
 * @param string $tag The tag name
 * @return TaggedIteratorArgument
 */
function tagged_services(string $tag): TaggedIteratorArgument
{
    return new TaggedIteratorArgument($tag);
}

/**
 * Helper function to create a service resolver closure.
 *
 * @param string $id The service identifier
 * @return ServiceClosureArgument
 */
function service_closure(string $id): ServiceClosureArgument
{
    return new ServiceClosureArgument(service($id));
}
