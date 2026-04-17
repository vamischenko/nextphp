<?php

declare(strict_types=1);

namespace Nextphp\Console\Installer;

final class ProjectInstaller
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $templatesRoot,
    ) {
    }

    public function installSkeleton(string $targetPath): void
    {
        $this->copyTemplate('skeleton', $targetPath);
    }

    public function installApiSkeleton(string $targetPath): void
    {
        $this->copyTemplate('api-skeleton', $targetPath);
    }

    private function copyTemplate(string $template, string $targetPath): void
    {
        $sourcePath = rtrim($this->templatesRoot, '/') . '/' . $template;
        if (! is_dir($sourcePath)) {
            throw new \RuntimeException(sprintf('Template "%s" not found.', $template));
        }

        $this->copyDirectory($sourcePath, $targetPath);
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (! is_dir($target) && ! mkdir($target, 0777, true) && ! is_dir($target)) {
            throw new \RuntimeException('Failed to create target directory: ' . $target);
        }

        $items = scandir($source);
        if ($items === false) {
            throw new \RuntimeException('Failed to read directory: ' . $source);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $src = $source . '/' . $item;
            $dst = $target . '/' . $item;

            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
                continue;
            }

            copy($src, $dst);
        }
    }
}
