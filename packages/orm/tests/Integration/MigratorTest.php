<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Integration;

use Nextphp\Orm\Connection\Connection;
use Nextphp\Orm\Migration\Migrator;
use Nextphp\Orm\Schema\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migrator::class)]
final class MigratorTest extends TestCase
{
    private Connection $db;

    private string $migrationsPath;

    protected function setUp(): void
    {
        $this->db = Connection::sqlite();
        $this->migrationsPath = sys_get_temp_dir() . '/nextphp_migrations_' . uniqid();
        mkdir($this->migrationsPath);
    }

    protected function tearDown(): void
    {
        // Cleanup migration files
        foreach (glob($this->migrationsPath . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->migrationsPath)) {
            rmdir($this->migrationsPath);
        }
    }

    private function createMigrationFile(string $name, string $upSql, string $downSql): string
    {
        $className = 'Migration_' . str_replace(['-', ' '], '_', $name);
        $content = <<<PHP
<?php
use Nextphp\Orm\Migration\Migration;
class {$className} extends Migration {
    public function up(): void {
        \$this->connection->statement('{$upSql}');
    }
    public function down(): void {
        \$this->connection->statement('{$downSql}');
    }
}
PHP;
        $file = $this->migrationsPath . '/' . $name . '.php';
        file_put_contents($file, $content);

        return $file;
    }

    #[Test]
    public function migrateRunsPendingMigrations(): void
    {
        $this->createMigrationFile(
            '2024_01_01_000000_create_articles',
            'CREATE TABLE articles (id INTEGER PRIMARY KEY)',
            'DROP TABLE IF EXISTS articles',
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);
        $ran = $migrator->migrate();

        self::assertCount(1, $ran);

        $schema = new Schema($this->db);
        self::assertTrue($schema->hasTable('articles'));
    }

    #[Test]
    public function migrateIsIdempotent(): void
    {
        $this->createMigrationFile(
            '2024_01_01_000001_create_tags',
            'CREATE TABLE tags (id INTEGER PRIMARY KEY)',
            'DROP TABLE IF EXISTS tags',
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);
        $migrator->migrate();
        $ran = $migrator->migrate();

        // Second run should have no pending migrations
        self::assertCount(0, $ran);
    }

    #[Test]
    public function rollbackUndoesLastBatch(): void
    {
        $this->createMigrationFile(
            '2024_01_02_000000_create_categories',
            'CREATE TABLE categories (id INTEGER PRIMARY KEY)',
            'DROP TABLE IF EXISTS categories',
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);
        $migrator->migrate();
        $rolledBack = $migrator->rollback();

        self::assertCount(1, $rolledBack);

        $schema = new Schema($this->db);
        self::assertFalse($schema->hasTable('categories'));
    }

    #[Test]
    public function getRanReturnsExecutedMigrations(): void
    {
        $this->createMigrationFile(
            '2024_01_03_000000_create_labels',
            'CREATE TABLE labels (id INTEGER PRIMARY KEY)',
            'DROP TABLE IF EXISTS labels',
        );

        $migrator = new Migrator($this->db, $this->migrationsPath);
        self::assertEmpty($migrator->getRan());

        $migrator->migrate();

        self::assertCount(1, $migrator->getRan());
    }

    #[Test]
    public function emptyMigrationsDir(): void
    {
        $migrator = new Migrator($this->db, $this->migrationsPath);
        $ran = $migrator->migrate();

        self::assertEmpty($ran);
    }
}
