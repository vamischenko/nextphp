<?php

declare(strict_types=1);

namespace Nextphp\Validation\Exception;

use Nextphp\Validation\ValidationResult;

final class ValidationException extends \Nextphp\Core\Exception\NextphpException
{
    /**
      * @psalm-mutation-free
     */
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
