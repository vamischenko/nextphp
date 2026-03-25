<?php

declare(strict_types=1);

namespace Nextphp\Validation;

use Nextphp\Validation\Attribute\Email;
use Nextphp\Validation\Attribute\Max;
use Nextphp\Validation\Attribute\Min;
use Nextphp\Validation\Attribute\Required;
use ReflectionClass;

/**
 * Validates an object (DTO) using PHP attributes on its properties.
 *
 * Supported attributes: #[Required], #[Email], #[Min(n)], #[Max(n)]
 *
 * @example
 *   class CreateUserRequest {
 *       #[Required] #[Email]
 *       public string $email = '';
 *
 *       #[Required] #[Min(3)] #[Max(50)]
 *       public string $name = '';
 *   }
 *
 *   $result = AttributeValidator::validate(new CreateUserRequest());
 */
final class AttributeValidator
{
    /**
     * @param object $dto
     */
    public static function validate(object $dto): ValidationResult
    {
        $errors    = [];
        $ref       = new ReflectionClass($dto);
        $validator = new Validator();

        foreach ($ref->getProperties() as $property) {
            $property->setAccessible(true);
            $field = $property->getName();
            $value = $property->isInitialized($dto) ? $property->getValue($dto) : null;

            $rules = self::rulesFromProperty($property);
            if ($rules === []) {
                continue;
            }

            /** @var array<string, mixed> $data */
            $data   = [$field => $value];
            $result = $validator->validate($data, [$field => $rules]);

            foreach ($result->errors() as $f => $messages) {
                $errors[$f] = array_merge($errors[$f] ?? [], $messages);
            }
        }

        return new ValidationResult($errors);
    }

    /**
     * @param \ReflectionProperty $property
     * @return list<string|ValidationRuleInterface>
     */
    private static function rulesFromProperty(\ReflectionProperty $property): array
    {
        $rules = [];

        foreach ($property->getAttributes() as $attr) {
            $name = $attr->getName();

            if ($name === Required::class) {
                $rules[] = 'required';
            } elseif ($name === Email::class) {
                $rules[] = 'email';
            } elseif ($name === Min::class) {
                /** @var Min $instance */
                $instance = $attr->newInstance();
                $rules[]  = 'min:' . $instance->value;
            } elseif ($name === Max::class) {
                /** @var Max $instance */
                $instance = $attr->newInstance();
                $rules[]  = 'max:' . $instance->value;
            }
        }

        return $rules;
    }
}
