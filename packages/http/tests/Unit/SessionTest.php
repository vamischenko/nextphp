<?php

declare(strict_types=1);

namespace Nextphp\Http\Tests\Unit;

use Nextphp\Http\Handler\CallableHandler;
use Nextphp\Http\Message\Response;
use Nextphp\Http\Message\ServerRequest;
use Nextphp\Http\Session\ArraySession;
use Nextphp\Http\Session\FileSession;
use Nextphp\Http\Session\SessionMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(ArraySession::class)]
#[CoversClass(FileSession::class)]
#[CoversClass(SessionMiddleware::class)]
final class SessionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ArraySession
    // -------------------------------------------------------------------------

    #[Test]
    public function arraySessionSetAndGet(): void
    {
        $session = new ArraySession();
        $session->start();
        $session->set('key', 'value');

        self::assertTrue($session->has('key'));
        self::assertSame('value', $session->get('key'));
    }

    #[Test]
    public function arraySessionGetDefault(): void
    {
        $session = new ArraySession();
        $session->start();

        self::assertSame('default', $session->get('missing', 'default'));
    }

    #[Test]
    public function arraySessionForget(): void
    {
        $session = new ArraySession();
        $session->start();
        $session->set('x', 1);
        $session->forget('x');

        self::assertFalse($session->has('x'));
    }

    #[Test]
    public function arraySessionFlush(): void
    {
        $session = new ArraySession();
        $session->start();
        $session->set('a', 1);
        $session->set('b', 2);
        $session->flush();

        self::assertSame([], $session->all());
    }

    #[Test]
    public function arraySessionRegenerate(): void
    {
        $session = new ArraySession('original-id');
        $session->regenerate();

        self::assertNotSame('original-id', $session->getId());
    }

    // -------------------------------------------------------------------------
    // FileSession
    // -------------------------------------------------------------------------

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/nextphp_sess_' . uniqid();
        mkdir($dir, 0o755, true);

        return $dir;
    }

    #[Test]
    public function fileSessionPersistsData(): void
    {
        $dir = $this->makeTempDir();
        $id = 'test-session-id';

        $session = new FileSession($dir, $id);
        $session->start();
        $session->set('user', 42);
        $session->save();

        // Load in a new instance with the same ID
        $session2 = new FileSession($dir, $id);
        $session2->start();

        self::assertSame(42, $session2->get('user'));
    }

    #[Test]
    public function fileSessionRegenerateDeletesOldFile(): void
    {
        $dir = $this->makeTempDir();
        $id = 'old-id';

        $session = new FileSession($dir, $id);
        $session->start();
        $session->set('x', 1);
        $session->save();

        $oldFile = $dir . '/sess_' . $id;
        self::assertFileExists($oldFile);

        $session->regenerate();
        self::assertFileDoesNotExist($oldFile);
    }

    // -------------------------------------------------------------------------
    // SessionMiddleware
    // -------------------------------------------------------------------------

    #[Test]
    public function middlewareStartsSessionAndSetsAttribute(): void
    {
        $session = new ArraySession();
        $mw = new SessionMiddleware($session);

        $request = new ServerRequest('GET', 'http://example.com');
        $handler = new CallableHandler(static function (ServerRequestInterface $req): ResponseInterface {
            $sess = $req->getAttribute('session');
            self::assertInstanceOf(ArraySession::class, $sess);

            return new Response(200);
        });

        $mw->process($request, $handler);
    }

    #[Test]
    public function middlewareWritesSessionCookie(): void
    {
        $session = new ArraySession('fixed-id');
        $mw = new SessionMiddleware($session, cookieName: 'SESS');

        $request = new ServerRequest('GET', 'http://example.com');
        $handler = new CallableHandler(static fn (): ResponseInterface => new Response(200));
        $response = $mw->process($request, $handler);

        $setCookie = $response->getHeader('Set-Cookie');
        self::assertNotEmpty($setCookie);
        self::assertStringContainsString('SESS=fixed-id', $setCookie[0]);
    }
}
