<?php

namespace Arakne\Spinneret\Form\Constraint;

use Attribute;
use Override;
use Quatrevieux\Form\RegistryInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintValidatorInterface;
use Quatrevieux\Form\Validator\FieldError;

/**
 * Set the current field as required if another field is blank.
 *
 * @implements ConstraintValidatorInterface<RequiredIfOtherIsBlank>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class RequiredIfOtherIsBlank implements ConstraintInterface, ConstraintValidatorInterface
{
    public const string CODE = 'd618c51a-96e1-58a5-ba97-eb3f7f9806e8';

    private const string BASE_MESSAGE = 'This field is required if {{ field }} is not provided.';
    private const string EXCLUSIVE_MESSAGE = 'This field must be provided if {{ field }} is not provided.';

    public function __construct(
        /**
         * The field name to check. If the field is blank, the current field will be required.
         *
         * @var string
         */
        public string $field,

        /**
         * The error message to display if the validation fails.
         * The placeholder {{ field }} will be replaced by the field name defined in the "field" property.
         *
         * If not provided, the default message will be used, depending on the "exclusive" property.
         */
        public ?string $message = null,

        /**
         * If true, the current field must be blank if the other field is provided.
         */
        public bool $exclusive = false,
    ) {}

    #[Override]
    public function getValidator(RegistryInterface $registry): ConstraintValidatorInterface
    {
        return $this;
    }

    #[Override]
    public function validate(ConstraintInterface $constraint, mixed $value, object $data): ?FieldError
    {
        $currentFieldIsBlank = self::isBlank($value);
        $otherIsBlank = self::isBlank($data->{$constraint->field} ?? null);

        if ($currentFieldIsBlank && $otherIsBlank) {
            return new FieldError(
                message: $this->message ?? ($constraint->exclusive ? self::EXCLUSIVE_MESSAGE : self::BASE_MESSAGE),
                parameters: ['field' => $constraint->field],
                code: self::CODE,
            );
        }

        if (!$currentFieldIsBlank && !$otherIsBlank && $constraint->exclusive) {
            return new FieldError(
                message: $this->message ?? self::EXCLUSIVE_MESSAGE,
                parameters: ['field' => $constraint->field],
                code: self::CODE,
            );
        }

        return null;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
