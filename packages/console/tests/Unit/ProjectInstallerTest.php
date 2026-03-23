<?php

declare(strict_types=1);

namespace Nextphp\Console\Tests\Unit;

use Nextphp\Console\Installer\ProjectInstaller;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectInstaller::class)]
final class ProjectInstallerTest extends TestCase
{
    #[Test]
    public function installsSkeletonTemplate(): void
    {
        $templates = sys_get_temp_dir() . '/nextphp_templates_' . uniqid();
        $target = sys_get_temp_dir() . '/nextphp_target_' . uniqid();

        mkdir($templates . '/skeleton/public', 0777, true);
        file_put_contents($templates . '/skeleton/public/index.php', '<?php echo "ok";');

        $installer = new ProjectInstaller($templates);
        $installer->installSkeleton($target);

        self::assertFileExists($target . '/public/index.php');
    }
}
