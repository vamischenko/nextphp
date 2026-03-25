<?php

declare(strict_types=1);

namespace Nextphp\Mail;

use Nextphp\Mail\Http\HttpClientInterface;
use Nextphp\Mail\Http\StreamHttpClient;
use RuntimeException;

/**
 * Postmark mailer via the Postmark Messages API.
 */
final class PostmarkMailer implements MailerInterface
{
    private const API_URL = 'https://api.postmarkapp.com/email';

    public function __construct(
        private readonly string $serverToken,
        private readonly string $from,
        private readonly HttpClientInterface $http = new StreamHttpClient(),
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $payload = (string) json_encode([
            'From'     => $this->from,
            'To'       => $mailable->to(),
            'Subject'  => $mailable->subject(),
            'TextBody' => $mailable->text(),
            'HtmlBody' => $mailable->html(),
        ], JSON_THROW_ON_ERROR);

        $headers = [
            'Accept'                  => 'application/json',
            'Content-Type'            => 'application/json',
            'X-Postmark-Server-Token' => $this->serverToken,
        ];

        $response = $this->http->post(self::API_URL, $headers, $payload);

        if ($response['status'] >= 400) {
            throw new RuntimeException("Postmark: send failed (HTTP {$response['status']}): {$response['body']}");
        }
    }
}
