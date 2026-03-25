<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Thrown when a mock expectation is violated (wrong args, wrong call count, etc.).
 */
final class MockExpectationException extends \RuntimeException
{
}
