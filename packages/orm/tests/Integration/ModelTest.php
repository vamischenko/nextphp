<?php

declare(strict_types=1);

namespace Nextphp\Orm\Tests\Integration;

use Nextphp\Orm\Connection\Connection;
use Nextphp\Orm\Exception\ModelNotFoundException;
use Nextphp\Orm\Model\Model;
use Nextphp\Orm\Model\ModelEvent;
use Nextphp\Orm\Model\Relations\HasMany;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Model::class)]
final class ModelTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::sqlite();

        $this->db->statement(
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT, active INTEGER DEFAULT 1, deleted_at TIMESTAMP NULL, created_at TIMESTAMP, updated_at TIMESTAMP)',
        );
        $this->db->statement(
            'CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT NOT NULL, created_at TIMESTAMP, updated_at TIMESTAMP)',
        );

        UserModel::setDefaultConnection($this->db);
        PostModel::setDefaultConnection($this->db);
    }

    #[Test]
    public function createAndFind(): void
    {
        $user = UserModel::create(['name' => 'Alice', 'email' => 'alice@example.com']);

        self::assertNotNull($user->getKey());
        self::assertSame('Alice', $user->getAttribute('name'));

        $found = UserModel::find((int) $user->getKey());

        self::assertNotNull($found);
        self::assertSame('Alice', $found->getAttribute('name'));
    }

    #[Test]
    public function findOrFail(): void
    {
        UserModel::create(['name' => 'Bob']);

        $this->expectException(ModelNotFoundException::class);
        UserModel::findOrFail(999);
    }

    #[Test]
    public function updateModel(): void
    {
        $user = UserModel::create(['name' => 'Carol']);
        $user->update(['name' => 'Caroline']);

        $found = UserModel::find((int) $user->getKey());
        self::assertSame('Caroline', $found->getAttribute('name'));
    }

    #[Test]
    public function deleteModel(): void
    {
        $user = UserModel::create(['name' => 'Dave']);
        $key = $user->getKey();
        $user->delete();

        self::assertNull(UserModel::find((int) $key));
    }

    #[Test]
    public function all(): void
    {
        UserModel::create(['name' => 'A']);
        UserModel::create(['name' => 'B']);
        UserModel::create(['name' => 'C']);

        $all = UserModel::all();

        self::assertCount(3, $all);
    }

    #[Test]
    public function dirtyAttributes(): void
    {
        $user = UserModel::create(['name' => 'Eve']);
        $user->setAttribute('name', 'Evelyn');

        self::assertTrue($user->isDirty('name'));
        self::assertFalse($user->isDirty('email'));
    }

    #[Test]
    public function modelEvents(): void
    {
        $log = [];

        UserModel::on(ModelEvent::Creating, function ($model) use (&$log) {
            $log[] = 'creating';
        });

        UserModel::on(ModelEvent::Created, function ($model) use (&$log) {
            $log[] = 'created';
        });

        UserModel::create(['name' => 'Frank']);

        self::assertContains('creating', $log);
        self::assertContains('created', $log);
    }

    #[Test]
    public function hasManyRelation(): void
    {
        $user = UserModel::create(['name' => 'Grace']);
        $userId = (int) $user->getKey();

        $this->db->insert('INSERT INTO posts (user_id, title) VALUES (?, ?)', [$userId, 'Post 1']);
        $this->db->insert('INSERT INTO posts (user_id, title) VALUES (?, ?)', [$userId, 'Post 2']);

        $found = UserModel::find($userId);
        $posts = $found->posts;

        self::assertIsArray($posts);
        self::assertCount(2, $posts);
        self::assertInstanceOf(PostModel::class, $posts[0]);
    }

    #[Test]
    public function lazyLoadingRelation(): void
    {
        $user = UserModel::create(['name' => 'Henry']);
        $userId = (int) $user->getKey();
        $this->db->insert('INSERT INTO posts (user_id, title) VALUES (?, ?)', [$userId, 'HP1']);

        $found = UserModel::find($userId);

        // First access loads and caches
        $posts1 = $found->posts;
        $posts2 = $found->posts;

        self::assertSame($posts1, $posts2);
    }

    #[Test]
    public function fillableFiltersAttributes(): void
    {
        $user = new UserModel();
        $user->fill(['name' => 'Ivan', 'active' => 0, 'email' => 'ivan@example.com']);

        self::assertSame('Ivan', $user->getAttribute('name'));
        // 'active' is not in fillable => should not be set
        self::assertNull($user->getAttribute('active'));
    }

    #[Test]
    public function queryStaticMethod(): void
    {
        UserModel::create(['name' => 'Jack']);
        UserModel::create(['name' => 'Jill']);

        $count = UserModel::query()->count();

        self::assertSame(2, $count);
    }

    #[Test]
    public function getTableAutoDerived(): void
    {
        $model = new AutoDerivedModel();

        self::assertSame('auto_derived_models', $model->getTable());
    }

    #[Test]
    public function globalScopeAppliesToQueries(): void
    {
        UserModel::addGlobalScope(static function ($query): void {
            $query->where('name', '!=', 'blocked');
        });

        UserModel::create(['name' => 'allowed']);
        UserModel::create(['name' => 'blocked']);

        $all = UserModel::all();
        self::assertCount(1, $all);
        self::assertSame('allowed', $all[0]->getAttribute('name'));
    }

    #[Test]
    public function lazyLoadingGuardThrowsWhenEnabled(): void
    {
        $user = UserModel::create(['name' => 'guarded']);
        UserModel::preventLazyLoading(true);

        try {
            $this->expectException(\Nextphp\Orm\Exception\OrmException::class);
            $unused = $user->posts;
        } finally {
            UserModel::preventLazyLoading(false);
        }
    }

    #[Test]
    public function softDeleteExcludesFromDefaultQueriesAndCanIncludeTrashed(): void
    {
        $user = SoftUserModel::create(['name' => 'soft']);
        $id = (int) $user->getKey();
        $user->delete();

        self::assertNull(SoftUserModel::find($id));
        $trashed = SoftUserModel::withTrashed()->where('id', '=', $id)->first();
        self::assertNotNull($trashed);
    }
}

// --- Test fixtures ---

final class UserModel extends Model
{
    protected string $table = 'users';

    /** @var string[] */
    protected array $fillable = ['name', 'email'];

    public function posts(): HasMany
    {
        return $this->hasMany(PostModel::class, 'user_id', 'id');
    }
}

final class PostModel extends Model
{
    protected string $table = 'posts';

    /** @var string[] */
    protected array $fillable = ['title', 'user_id'];
}

final class AutoDerivedModel extends Model
{
    /** @var string[] */
    protected array $fillable = [];
}

final class SoftUserModel extends Model
{
    protected string $table = 'users';

    protected bool $softDelete = true;

    /** @var string[] */
    protected array $fillable = ['name'];
}
