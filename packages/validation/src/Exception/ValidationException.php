<?php

declare(strict_types=1);

namespace Nextphp\Validation\Exception;

use Nextphp\Validation\ValidationResult;
use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly ValidationResult $result,
    ) {
        parent::__construct('The given data was invalid.');
    }

    public function getResult(): ValidationResult
    {
        return $this->result;
    }
}
