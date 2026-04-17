<?php

declare(strict_types=1);

namespace Nextphp\Validation;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;
use Nextphp\Validation\Exception\ValidationException;

abstract class FormRequest
{
    private ?ValidationResult $result = null;

    /**
     * Define the validation rules for this request.
     *
     * @return array<string, string|array<int, string|ValidationRuleInterface>>
     * @psalm-impure
     */
    abstract public function rules(): array;

    /**
     * Validate the given data against the rules.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException when validation fails
     */
    public function validate(array $data, ?PresenceVerifierInterface $presence = null): ValidationResult
    {
        $validator = new Validator($presence);
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->result = $validator->validate($data, $this->rules());

        if ($this->result->fails()) {
            throw new ValidationException($this->result);
        }

        return $this->result;
    }

    /**
     * Return the last validation result without throwing.
     *
     * @param array<string, mixed> $data
     */
    public function validateSilently(array $data, ?PresenceVerifierInterface $presence = null): ValidationResult
    {
        $validator = new Validator($presence);
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->result = $validator->validate($data, $this->rules());

        return $this->result;
    }

    /**
     * Access the validated result after calling validate().
     *
     * @throws \LogicException when called before validate()
       * @psalm-mutation-free
     */
    public function validated(): ValidationResult
    {
        if ($this->result === null) {
            throw new \LogicException('Call validate() or validateSilently() before accessing validated().');
        }

        return $this->result;
    }
}
