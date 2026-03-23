<?php

declare(strict_types=1);

namespace Nextphp\Validation;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;
use Nextphp\Validation\Rule\EmailRule;
use Nextphp\Validation\Rule\ExistsRule;
use Nextphp\Validation\Rule\MaxRule;
use Nextphp\Validation\Rule\MinRule;
use Nextphp\Validation\Rule\RequiredRule;
use Nextphp\Validation\Rule\UniqueRule;

final class Validator
{
    public function __construct(
        private readonly ?PresenceVerifierInterface $presence = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, string|ValidationRuleInterface>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $ruleObject = $this->normalizeRule($rule);
                $error = $ruleObject->validate($field, $value, $data);
                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return new ValidationResult($errors);
    }

    private function normalizeRule(string|ValidationRuleInterface $rule): ValidationRuleInterface
    {
        if ($rule instanceof ValidationRuleInterface) {
            return $rule;
        }

        if ($rule === 'required') {
            return new RequiredRule();
        }

        if ($rule === 'email') {
            return new EmailRule();
        }

        if (str_starts_with($rule, 'min:')) {
            return new MinRule((int) substr($rule, 4));
        }

        if (str_starts_with($rule, 'max:')) {
            return new MaxRule((int) substr($rule, 4));
        }

        if (str_starts_with($rule, 'unique:')) {
            $this->assertPresenceVerifier();
            [$table, $column] = $this->parsePresenceRule($rule, 'unique:');

            return new UniqueRule($this->presence, $table, $column);
        }

        if (str_starts_with($rule, 'exists:')) {
            $this->assertPresenceVerifier();
            [$table, $column] = $this->parsePresenceRule($rule, 'exists:');

            return new ExistsRule($this->presence, $table, $column);
        }

        throw new \InvalidArgumentException(sprintf('Unknown validation rule: "%s".', $rule));
    }

    private function assertPresenceVerifier(): void
    {
        if ($this->presence === null) {
            throw new \InvalidArgumentException('Presence verifier is required for unique/exists rules.');
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parsePresenceRule(string $rule, string $prefix): array
    {
        $parts = explode(',', substr($rule, strlen($prefix)));
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? '';

        if ($table === '' || $column === '') {
            throw new \InvalidArgumentException(sprintf('Invalid %s rule format.', rtrim($prefix, ':')));
        }

        return [$table, $column];
    }
}
