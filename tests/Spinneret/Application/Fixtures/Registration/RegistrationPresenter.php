<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\User;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\UserRepository;
use Quatrevieux\Form\Validator\FieldError;

/**
 * @implements PresenterInterface<RegistrationRequest>
 */
final readonly class RegistrationPresenter implements PresenterInterface
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        $user = new User(
            $request->name,
            $request->email,
            $request->password,
        );

        $this->repository->add($user);

        return new RegistrationSuccessResponse($user);
    }

    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        $errors = array_map(
            fn(FieldError $error) => $error->localizedMessage(),
            $routedRequest->form->errors()
        );

        return new RegistrationErrorResponse($errors);
    }
}
