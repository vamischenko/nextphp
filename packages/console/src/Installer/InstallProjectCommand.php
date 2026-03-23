<?php

declare(strict_types=1);

namespace Nextphp\Console\Installer;

use Nextphp\Console\Command;

final class InstallProjectCommand extends Command
{
    public function __construct(
        private readonly ProjectInstaller $installer,
    ) {
        parent::__construct('project:install', 'Install skeleton or api-skeleton project');
    }

    public function handle(array $arguments, array $options = []): int
    {
        $template = $arguments[0] ?? 'skeleton';
        $target = $arguments[1] ?? getcwd();
        if (! is_string($target) || $target === '') {
            return 1;
        }

        if ($template === 'api-skeleton') {
            $this->installer->installApiSkeleton($target);

            return 0;
        }

        $this->installer->installSkeleton($target);

        return 0;
    }
}
