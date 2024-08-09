<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Registration;

use Arakne\Tests\Spinneret\Application\Fixtures\Registration\Constraint\UniqueName;
use Quatrevieux\Form\Validator\Constraint\Length;
use Quatrevieux\Form\Validator\Constraint\PasswordStrength;
use Quatrevieux\Form\Validator\Constraint\ValidateVar;

final class RegistrationRequest
{
    #[Length(min: 2, max: 25), UniqueName]
    public string $name;

    #[ValidateVar(FILTER_VALIDATE_EMAIL)]
    public string $email;

    #[PasswordStrength(min:20)]
    public string $password;
}
