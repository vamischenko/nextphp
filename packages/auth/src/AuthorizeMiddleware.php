<?php

declare(strict_types=1);

namespace Nextphp\Auth;

final class AuthorizeMiddleware
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly PolicyRegistry $policies,
    ) {
    }

    /**
     * @param callable(): mixed $next
     */
    public function handle(string $ability, callable $next, mixed ...$arguments): mixed
    {
        if (! $this->policies->allows($ability, ...$arguments)) {
            throw new AuthorizationException(sprintf('Not authorized for ability "%s".', $ability));
        }

        return $next();
    }
}
