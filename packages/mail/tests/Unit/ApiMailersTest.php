<?php

declare(strict_types=1);

namespace Nextphp\Mail\Tests\Unit;

use Nextphp\Mail\Http\HttpClientInterface;
use Nextphp\Mail\Mailable;
use Nextphp\Mail\MailgunMailer;
use Nextphp\Mail\PostmarkMailer;
use Nextphp\Mail\SesMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SesMailer::class)]
#[CoversClass(MailgunMailer::class)]
#[CoversClass(PostmarkMailer::class)]
final class ApiMailersTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Stub Mailable
    // -------------------------------------------------------------------------

    private function makeMailable(): Mailable
    {
        return new class extends Mailable {
            public function subject(): string { return 'Test Subject'; }
            public function to(): string      { return 'user@example.com'; }
            public function html(): string    { return '<p>Hello</p>'; }
            public function text(): string    { return 'Hello'; }
        };
    }

    private function makeHttp(int $status = 200, string $body = '{"MessageId":"ok"}'): HttpClientInterface
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')->willReturn(['status' => $status, 'body' => $body]);
        return $http;
    }

    // -------------------------------------------------------------------------
    // SES
    // -------------------------------------------------------------------------

    #[Test]
    public function sesSendsWithSuccessfulResponse(): void
    {
        $mailer = new SesMailer('key', 'secret', 'from@example.com', http: $this->makeHttp(200));
        $mailer->send($this->makeMailable()); // must not throw
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function sesThrowsOn4xx(): void
    {
        $mailer = new SesMailer('key', 'secret', 'from@example.com', http: $this->makeHttp(400, 'Bad Request'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/SES.*400/');
        $mailer->send($this->makeMailable());
    }

    #[Test]
    public function sesPostIncludesJsonPayload(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->willReturnCallback(static function (string $url, array $headers, string $body): array {
                $data = json_decode($body, true);
                self::assertSame('user@example.com', $data['Destination']['ToAddresses'][0]);
                self::assertSame('Test Subject', $data['Content']['Simple']['Subject']['Data']);
                return ['status' => 200, 'body' => '{}'];
            });

        (new SesMailer('key', 'secret', 'from@example.com', http: $http))->send($this->makeMailable());
    }

    // -------------------------------------------------------------------------
    // Mailgun
    // -------------------------------------------------------------------------

    #[Test]
    public function mailgunSendsWithSuccessfulResponse(): void
    {
        $mailer = new MailgunMailer('key', 'sandbox.mailgun.org', 'from@example.com', http: $this->makeHttp(200));
        $mailer->send($this->makeMailable());
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function mailgunThrowsOn4xx(): void
    {
        $mailer = new MailgunMailer('key', 'sandbox.mailgun.org', 'from@example.com', http: $this->makeHttp(401, 'Unauthorized'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Mailgun.*401/');
        $mailer->send($this->makeMailable());
    }

    #[Test]
    public function mailgunUsesEuEndpointWhenRegionIsEu(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->willReturnCallback(static function (string $url): array {
                self::assertStringContainsString('api.eu.mailgun.net', $url);
                return ['status' => 200, 'body' => '{}'];
            });

        (new MailgunMailer('key', 'sandbox.mailgun.org', 'from@example.com', region: 'eu', http: $http))
            ->send($this->makeMailable());
    }

    #[Test]
    public function mailgunPayloadContainsHtmlAndText(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->willReturnCallback(static function (string $url, array $headers, string $body): array {
                self::assertStringContainsString('<p>Hello</p>', $body);
                self::assertStringContainsString('Hello', $body);
                return ['status' => 200, 'body' => '{}'];
            });

        (new MailgunMailer('key', 'sandbox.mailgun.org', 'from@example.com', http: $http))
            ->send($this->makeMailable());
    }

    // -------------------------------------------------------------------------
    // Postmark
    // -------------------------------------------------------------------------

    #[Test]
    public function postmarkSendsWithSuccessfulResponse(): void
    {
        $mailer = new PostmarkMailer('token', 'from@example.com', http: $this->makeHttp(200));
        $mailer->send($this->makeMailable());
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function postmarkThrowsOn4xx(): void
    {
        $mailer = new PostmarkMailer('token', 'from@example.com', http: $this->makeHttp(422, 'Invalid'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Postmark.*422/');
        $mailer->send($this->makeMailable());
    }

    #[Test]
    public function postmarkPayloadContainsHtmlAndText(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->willReturnCallback(static function (string $url, array $headers, string $body): array {
                $data = json_decode($body, true);
                self::assertSame('<p>Hello</p>', $data['HtmlBody']);
                self::assertSame('Hello', $data['TextBody']);
                self::assertSame('Test Subject', $data['Subject']);
                return ['status' => 200, 'body' => '{}'];
            });

        (new PostmarkMailer('token', 'from@example.com', http: $http))->send($this->makeMailable());
    }

    #[Test]
    public function postmarkUsesServerTokenHeader(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->willReturnCallback(static function (string $url, array $headers): array {
                self::assertArrayHasKey('X-Postmark-Server-Token', $headers);
                self::assertSame('my-token', $headers['X-Postmark-Server-Token']);
                return ['status' => 200, 'body' => '{}'];
            });

        (new PostmarkMailer('my-token', 'from@example.com', http: $http))->send($this->makeMailable());
    }
}
