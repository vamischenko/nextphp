<?php

declare(strict_types=1);

namespace Nextphp\Migrations\Tests\Integration;

use Nextphp\Migrations\Migration\Migrator;
use Nextphp\Migrations\Schema\Schema;
use Nextphp\Orm\Connection\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migrator::class)]
final class MigratorTest extends TestCase
{
    #[Test]
    public function migrateAndRollbackLifecycle(): void
    {
        $db = Connection::sqlite();
        $dir = sys_get_temp_dir() . '/nextphp_migrations_pkg_' . uniqid();
        mkdir($dir);

        $content = <<<'PHP'
<?php
use Nextphp\Migrations\Migration\Migration;
class CreateSamples extends Migration {
    public function up(): void { $this->connection->statement('CREATE TABLE samples (id INTEGER PRIMARY KEY)'); }
    public function down(): void { $this->connection->statement('DROP TABLE IF EXISTS samples'); }
}
PHP;
        file_put_contents($dir . '/2026_01_01_000000_create_samples.php', $content);

        $migrator = new Migrator($db, $dir);
        $ran = $migrator->migrate();
        self::assertCount(1, $ran);
        self::assertTrue((new Schema($db))->hasTable('samples'));

        $rolled = $migrator->rollback();
        self::assertCount(1, $rolled);
        self::assertFalse((new Schema($db))->hasTable('samples'));
    }
}
