<?php

declare(strict_types=1);

namespace Nextphp\Testing\Browser;

use Nextphp\Testing\TestCase;

/**
 * Minimal browser testing base class.
 *
 * Uses Symfony Panther when installed; otherwise tests can extend this class
 * but must skip themselves if browser driver is unavailable.
 */
abstract class BrowserTestCase extends TestCase
{
    /**
     * @return object
     */
    protected function browser(): object
    {
        if (! class_exists('Symfony\\Component\\Panther\\PantherTestCase')) {
            throw new \RuntimeException('Panther is not installed. Require symfony/panther to use BrowserTestCase.');
        }

        if (! class_exists('Symfony\\Component\\Panther\\Client')) {
            throw new \RuntimeException('Panther Client class is not available.');
        }

        /** @var callable(): object $factory */
        $factory = 'Symfony\\Component\\Panther\\Client::createChromeClient';

        /** @psalm-suppress UndefinedClass */
        return $factory();
    }
}

