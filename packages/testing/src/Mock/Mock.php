<?php

declare(strict_types=1);

namespace Nextphp\Testing\Mock;

/**
 * Convenient facade for creating mocks.
 *
 * Example:
 *   $mailer = Mock::of(MailerInterface::class);
 *   $mailer->expects('send')->with('hello@example.com')->andReturn(true)->once();
 *   $mailer->verify();
 */
final class Mock
{
    /**
     * @template T of object
     * @param class-string<T> $classOrInterface
     * @return T&MockProxy
     */
    public static function of(string $classOrInterface): object
    {
        return MockBuilder::mock($classOrInterface);
    }
}
