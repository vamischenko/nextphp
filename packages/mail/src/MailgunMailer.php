<?php

declare(strict_types=1);

namespace Nextphp\Mail;

use Nextphp\Mail\Http\HttpClientInterface;
use Nextphp\Mail\Http\StreamHttpClient;
use RuntimeException;

/**
 * Mailgun mailer via the Mailgun Messages API.
 */
final class MailgunMailer implements MailerInterface
{
    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $domain,
        private readonly string $from,
        private readonly string $region = 'us',   // 'us' or 'eu'
        private readonly HttpClientInterface $http = new StreamHttpClient(),
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $base = $this->region === 'eu'
            ? 'https://api.eu.mailgun.net'
            : 'https://api.mailgun.net';

        $url = "{$base}/v3/{$this->domain}/messages";

        $boundary = '----NextphpMailBoundary' . bin2hex(random_bytes(8));
        $body     = $this->buildMultipart($mailable, $boundary);

        $headers = [
            'Authorization' => 'Basic ' . base64_encode('api:' . $this->apiKey),
            'Content-Type'  => "multipart/form-data; boundary={$boundary}",
        ];

        $response = $this->http->post($url, $headers, $body);

        if ($response['status'] >= 400) {
            throw new RuntimeException("Mailgun: send failed (HTTP {$response['status']}): {$response['body']}");
        }
    }

    private function buildMultipart(Mailable $mailable, string $boundary): string
    {
        $fields = [
            'from'    => $this->from,
            'to'      => $mailable->to(),
            'subject' => $mailable->subject(),
            'text'    => $mailable->text(),
            'html'    => $mailable->html(),
        ];

        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= $value . "\r\n";
        }
        $body .= "--{$boundary}--\r\n";

        return $body;
    }
}
