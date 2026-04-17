<?php

declare(strict_types=1);

namespace Nextphp\Validation;

use Nextphp\Validation\Contracts\PresenceVerifierInterface;
use Nextphp\Validation\Rule\ArrayRule;
use Nextphp\Validation\Rule\ClosureRule;
use Nextphp\Validation\Rule\BooleanRule;
use Nextphp\Validation\Rule\ConfirmedRule;
use Nextphp\Validation\Rule\EmailRule;
use Nextphp\Validation\Rule\ExistsRule;
use Nextphp\Validation\Rule\IntegerRule;
use Nextphp\Validation\Rule\MaxRule;
use Nextphp\Validation\Rule\MinRule;
use Nextphp\Validation\Rule\NullableRule;
use Nextphp\Validation\Rule\RequiredRule;
use Nextphp\Validation\Rule\UniqueRule;
use Nextphp\Validation\Translation\Translator;

final class Validator
{
    private string $locale = 'en';

    /** @var array<string, string> */
    private array $attributeNames = [];

    /** @var array<string, string> */
    private array $customMessages = [];

    private Translator $translator;

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly ?PresenceVerifierInterface $presence = null,
    ) {
        $this->translator = new Translator([
            'en' => require __DIR__ . '/Translation/lang/en.php',
            'ru' => require __DIR__ . '/Translation/lang/ru.php',
        ]);
    }

    /**
      * @psalm-pure
     */
    public static function make(?PresenceVerifierInterface $presence = null): self
    {
        return new self($presence);
    }

    /**
      * @psalm-external-mutation-free
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param array<string, string> $map field => human-readable name
       * @psalm-external-mutation-free
     */
    public function setAttributeNames(array $map): self
    {
        $this->attributeNames = $map;

        return $this;
    }

    /**
     * @param array<string, string> $map key => message (supports placeholders)
       * @psalm-external-mutation-free
     */
    public function setMessages(array $map): self
    {
        $this->customMessages = $map;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, mixed>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            // Support pipe-string syntax: 'required|email|max:255'
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $value = $data[$field] ?? null;
            $bail = in_array('bail', $fieldRules, strict: true);
            $nullable = in_array('nullable', $fieldRules, strict: true);

            // Skip all rules (except required) when value is null and field is nullable
            if ($nullable && $value === null) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                // Skip meta-rules
                if ($rule === 'bail' || $rule === 'nullable') {
                    continue;
                }

                $ruleObject = $this->normalizeRule($rule);
                $error = $ruleObject->validate($field, $value, $data);

                if ($error !== null) {
                    $errors[$field][] = $this->formatError($field, $error);

                    if ($bail) {
                        break;
                    }
                }
            }
        }

        return new ValidationResult($errors);
    }

    /**
      * @psalm-mutation-free
     */
    private function formatError(string $field, ValidationError|string $error): string
    {
        if (is_string($error)) {
            return $error;
        }

        $attribute = $this->attributeNames[$field] ?? $field;
        $params = array_merge(['attribute' => $attribute], $error->params);

        $custom = $this->customMessages[$error->key] ?? null;
        if (is_string($custom) && $custom !== '') {
            return $this->translator->trans($this->locale, $error->key, $params, $custom);
        }

        return $this->translator->trans($this->locale, $error->key, $params, $error->fallback);
    }

    /**
      * @psalm-mutation-free
     */
    private function normalizeRule(mixed $rule): ValidationRuleInterface
    {
        if ($rule instanceof ValidationRuleInterface) {
            return $rule;
        }

        if ($rule instanceof \Closure || (is_callable($rule) && !is_string($rule))) {
            return new ClosureRule($rule);
        }

        return match (true) {
            $rule === 'required'             => new RequiredRule(),
            $rule === 'email'                => new EmailRule(),
            $rule === 'boolean'              => new BooleanRule(),
            $rule === 'integer'              => new IntegerRule(),
            $rule === 'array'                => new ArrayRule(),
            $rule === 'confirmed'            => new ConfirmedRule(),
            $rule === 'nullable'             => new NullableRule(),
            str_starts_with($rule, 'min:')  => new MinRule((int) substr($rule, 4)),
            str_starts_with($rule, 'max:')  => new MaxRule((int) substr($rule, 4)),
            str_starts_with($rule, 'unique:') => $this->makeUniqueRule($rule),
            str_starts_with($rule, 'exists:') => $this->makeExistsRule($rule),
            default => throw new \InvalidArgumentException(sprintf('Unknown validation rule: "%s".', $rule)),
        };
    }

    /**
      * @psalm-mutation-free
     */
    private function makeUniqueRule(string $rule): UniqueRule
    {
        $this->assertPresenceVerifier();
        [$table, $column] = $this->parsePresenceRule($rule, 'unique:');

        return new UniqueRule($this->presence, $table, $column);
    }

    /**
      * @psalm-mutation-free
     */
    private function makeExistsRule(string $rule): ExistsRule
    {
        $this->assertPresenceVerifier();
        [$table, $column] = $this->parsePresenceRule($rule, 'exists:');

        return new ExistsRule($this->presence, $table, $column);
    }

    /**
      * @psalm-mutation-free
     */
    private function assertPresenceVerifier(): void
    {
        if ($this->presence === null) {
            throw new \InvalidArgumentException('Presence verifier is required for unique/exists rules.');
        }
    }

    /**
     * @return array{0: string, 1: string}
       * @psalm-pure
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
