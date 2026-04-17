<?php

declare(strict_types=1);

namespace Nextphp\Mail;

use Nextphp\Mail\Http\HttpClientInterface;
use Nextphp\Mail\Http\StreamHttpClient;
use RuntimeException;

/**
 * Amazon SES mailer via the SES v2 HTTP API (SendEmail action).
 *
 * Uses AWS Signature Version 4 (HMAC-SHA256).
 */
final class SesMailer implements MailerInterface
{
    private const SERVICE = 'ses';

    /**
      * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $from,
        private readonly string $region = 'us-east-1',
        private readonly HttpClientInterface $http = new StreamHttpClient(),
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $payload = $this->buildPayload($mailable);
        $url     = "https://email.{$this->region}.amazonaws.com/v2/email/outbound-emails";
        $date    = gmdate('Ymd\THis\Z');
        $day     = substr($date, 0, 8);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Amz-Date'   => $date,
            'Host'         => "email.{$this->region}.amazonaws.com",
            'Authorization' => $this->authorization($payload, $date, $day),
        ];

        $response = $this->http->post($url, $headers, $payload);

        if ($response['status'] >= 400) {
            throw new RuntimeException("SES: send failed (HTTP {$response['status']}): {$response['body']}");
        }
    }

    private function buildPayload(Mailable $mailable): string
    {
        return (string) json_encode([
            'FromEmailAddress' => $this->from,
            'Destination'      => ['ToAddresses' => [$mailable->to()]],
            'Content'          => [
                'Simple' => [
                    'Subject' => ['Data' => $mailable->subject(), 'Charset' => 'UTF-8'],
                    'Body'    => [
                        'Text' => ['Data' => $mailable->text(), 'Charset' => 'UTF-8'],
                        'Html' => ['Data' => $mailable->html(), 'Charset' => 'UTF-8'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
      * @psalm-mutation-free
     */
    private function authorization(string $payload, string $datetime, string $date): string
    {
        $host          = "email.{$this->region}.amazonaws.com";
        $canonicalUri  = '/v2/email/outbound-emails';
        $canonicalQuery = '';
        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-amz-date:{$datetime}\n";
        $signedHeaders   = 'content-type;host;x-amz-date';
        $payloadHash     = hash('sha256', $payload);

        $canonicalRequest = implode("\n", [
            'POST', $canonicalUri, $canonicalQuery,
            $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $scope       = "{$date}/{$this->region}/" . self::SERVICE . '/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256', $datetime, $scope, hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->signingKey($date);
        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);

        return "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    }

    /**
      * @psalm-mutation-free
     */
    private function signingKey(string $date): string
    {
        $kDate    = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, binary: true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, binary: true);
        $kService = hash_hmac('sha256', self::SERVICE, $kRegion, binary: true);

        return hash_hmac('sha256', 'aws4_request', $kService, binary: true);
    }
}
