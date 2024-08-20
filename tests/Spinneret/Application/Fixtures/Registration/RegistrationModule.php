<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Constraint\UniqueNameValidator;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\UserRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegistrationModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->post('/register', RegistrationRequest::class, RegistrationPresenter::class);

        $this->renderer(RegistrationSuccessResponse::class, RegistrationSuccessRenderer::class);
        $this->renderer(RegistrationErrorResponse::class, RegistrationErrorRenderer::class);

        $this->autowire(UniqueNameValidator::class, public: true);
        $this->autowire(UserRepository::class, public: true);
    }
}
