<?php

declare(strict_types=1);

namespace Nextphp\Queue;

interface JobInterface
{
    public function handle(): void;
}
