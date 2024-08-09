<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration\Constraint;

use Attribute;
use Quatrevieux\Form\RegistryInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintValidatorInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class UniqueName implements ConstraintInterface
{
    public function getValidator(RegistryInterface $registry): ConstraintValidatorInterface
    {
        return $registry->getConstraintValidator(UniqueNameValidator::class);
    }
}
