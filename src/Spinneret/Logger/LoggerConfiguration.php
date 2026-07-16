<?php

namespace Arakne\Spinneret\Logger;

use Closure;

/**
 * Configuration for the logger module.
 *
 * Unlike most configuration classes, this one is only used at compile time, so it's parameters
 * cannot be resolved using environment variables or other runtime values.
 * So, the cache should be cleared after changing the configuration.
 *
 * If you need to resolve some dynamic parameters, you can use:
 * - {@see LogChannel::$service} to define a custom logger service
 * - {@see LogChannel::$filter} to define runtime filters
 *
 * But use them in moderation, as they can make the logger slower.
 */
final readonly class LoggerConfiguration
{
    /**
     * @var array<LogChannel>
     */
    public array $channels;

    /**
     * @param LogChannel ...$channels
     */
    public function __construct(LogChannel ...$channels)
    {
        $this->channels = $channels;
    }

    /**
     * Add a new channel to the configuration.
     *
     * @param LogChannel $channel
     * @return self
     */
    public function with(LogChannel $channel): self
    {
        $channels = $this->channels;
        $channels[] = $channel;

        return new self(...$channels);
    }

    /**
     * Get the filter for the log channel of the given offset.
     *
     * @param string|int $offset The offset of the channel (i.e. the key in the configuration array)
     * @return (Closure(mixed, string|\Stringable, array<array-key, mixed>):bool)|null
     *
     * @internal Use by the module to build the logger filter
     */
    public function getFilter(string|int $offset): ?Closure
    {
        $channel = $this->channels[$offset] ?? null;

        return $channel?->filter;
    }
}
