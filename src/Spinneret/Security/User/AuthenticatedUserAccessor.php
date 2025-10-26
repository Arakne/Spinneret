<?php

namespace Arakne\Spinneret\Security\User;

use Arakne\Spinneret\Security\SecurityConfig;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Extract the user instance from the PSR request
 *
 * @template U as object
 */
final readonly class AuthenticatedUserAccessor
{
    public function __construct(
        /**
         * Attribute name used to access to the user instance from the psr-request
         *
         * @see SecurityConfig::$userAttribute
         */
        private string $userAttribute,

        /**
         * The expected user class name
         * If the class doesn't match, null will be returned
         *
         * @var class-string<U>|null
         */
        private ?string $userClassName = null,
    ) {}

    /**
     * Modify the expected user class name
     *
     * @param class-string<R> $userClassName
     * @return self<R>
     *
     * @template R as object
     */
    public function withClassName(string $userClassName): self
    {
        return new self(
            $this->userAttribute,
            $userClassName,
        );
    }

    /**
     * Extract the user from the PSR request
     *
     * @param ServerRequestInterface $psrRequest
     * @return U|null
     */
    public function get(ServerRequestInterface $psrRequest): ?object
    {
        $user = $psrRequest->getAttribute($this->userAttribute);

        if ($this->userClassName !== null && !($user instanceof $this->userClassName)) {
            return null;
        }

        return $user;
    }
}
