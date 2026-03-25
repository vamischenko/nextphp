# Mail

`nextphp/mail` — отправка писем через SMTP, SES, Mailgun, Postmark и очередь.

## Mailable

```php
use Nextphp\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function __construct(private readonly string $name) {}

    public function build(): static
    {
        return $this
            ->subject('Welcome to MyApp!')
            ->html("<h1>Hello, {$this->name}!</h1><p>Thanks for signing up.</p>")
            ->text("Hello, {$this->name}! Thanks for signing up.");
    }
}
```

## Отправка

```php
use Nextphp\Mail\MailerInterface;

$mailer->to('user@example.com')
       ->send(new WelcomeMail('Alice'));

// Через очередь
$mailer->to('user@example.com')
       ->queue(new WelcomeMail('Alice'));
```

## Драйверы

### SMTP

```php
use Nextphp\Mail\Mailer\SmtpMailer;

$mailer = new SmtpMailer(
    host: 'smtp.gmail.com',
    port: 587,
    username: 'user@gmail.com',
    password: getenv('MAIL_PASSWORD'),
    encryption: 'tls',
);
```

### AWS SES

```php
use Nextphp\Mail\Mailer\SesMailer;

$mailer = new SesMailer(
    region: 'us-east-1',
    accessKey: getenv('AWS_KEY'),
    secretKey: getenv('AWS_SECRET'),
);
```

### Mailgun

```php
use Nextphp\Mail\Mailer\MailgunMailer;

$mailer = new MailgunMailer(
    apiKey: getenv('MAILGUN_KEY'),
    domain: 'mg.example.com',
    region: 'EU', // или 'US' по умолчанию
);
```

### Postmark

```php
use Nextphp\Mail\Mailer\PostmarkMailer;

$mailer = new PostmarkMailer(apiKey: getenv('POSTMARK_KEY'));
```

## Вложения

```php
$mailable->attach('/path/to/invoice.pdf', 'invoice.pdf', 'application/pdf');
$mailable->attachData($pdfContent, 'receipt.pdf', 'application/pdf');
```

## Тестирование

```php
use Nextphp\Mail\Mailer\ArrayMailer;

$mailer = new ArrayMailer();

$mailer->to('user@example.com')->send(new WelcomeMail('Alice'));

$sent = $mailer->sent();
self::assertCount(1, $sent);
self::assertSame('user@example.com', $sent[0]->getTo());
```
