<?php

namespace Arakne\Spinneret\Security\Attribute;

use Arakne\Spinneret\Container\Value\DependentValueInterface;
use Arakne\Spinneret\Security\User\AuthenticatedUserAccessor;
use Attribute;
use Override;
use Psr\Container\ContainerInterface;

use function assert;
use function sprintf;
use function var_export;

/**
 * Inject the instance of {@see AuthenticatedUserAccessor} on the marked parameter
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class UserAccessor implements DependentValueInterface
{
    public function __construct(
        /**
         * The expected user class name
         *
         * @var class-string|null
         */
        private ?string $userClassName = null,
    ) {}

    #[Override]
    public function dependencies(): array
    {
        return [AuthenticatedUserAccessor::class];
    }

    #[Override]
    public function resolve(ContainerInterface $container): ?object
    {
        $accessor = $container->get(AuthenticatedUserAccessor::class);
        assert($accessor instanceof AuthenticatedUserAccessor);

        if ($this->userClassName !== null) {
            $accessor = $accessor->withClassName($this->userClassName);
        }

        return $accessor;
    }

    #[Override]
    public function compile(): string
    {
        $code = sprintf('$this->get(%s)', var_export(AuthenticatedUserAccessor::class, true));

        if ($this->userClassName !== null) {
            $code .= sprintf('->withClassName(%s)', var_export($this->userClassName, true));
        }

        return $code;
    }

    #[Override]
    public function type(): ?string
    {
        return $this->userClassName ?? 'object';
    }
}
