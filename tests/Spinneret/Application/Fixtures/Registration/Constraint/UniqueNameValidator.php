<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration\Constraint;

use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Domain\UserRepository;
use Quatrevieux\Form\Validator\Constraint\ConstraintInterface as C;
use Quatrevieux\Form\Validator\Constraint\ConstraintValidatorInterface;
use Quatrevieux\Form\Validator\FieldError;

final readonly class UniqueNameValidator implements ConstraintValidatorInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function validate(C $constraint, mixed $value, object $data): FieldError|array|null
    {
        if ($this->userRepository->findByName($value) !== null) {
            return new FieldError('Name already exists');
        }

        return null;
    }
}
