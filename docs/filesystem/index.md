# Filesystem

`nextphp/filesystem` — единый интерфейс для работы с файловой системой (Local, S3, FTP, SFTP, Flysystem).

## Создание драйвера

```php
use Nextphp\Filesystem\CacheFactory; // через FilesystemFactory
use Nextphp\Filesystem\LocalFilesystem;
use Nextphp\Filesystem\S3Filesystem;
use Nextphp\Filesystem\FtpFilesystem;
use Nextphp\Filesystem\SftpFilesystem;

// Local
$fs = new LocalFilesystem('/storage/uploads');

// S3
$fs = new S3Filesystem(
    bucket: 'my-bucket',
    region: 'us-east-1',
    accessKey: getenv('AWS_KEY'),
    secretKey: getenv('AWS_SECRET'),
);

// FTP
use Nextphp\Filesystem\Ftp\NativeFtpClient;
$fs = new FtpFilesystem(new NativeFtpClient('ftp.example.com', 'user', 'pass'));

// SFTP
use Nextphp\Filesystem\Sftp\Ssh2SftpClient;
$fs = new SftpFilesystem(new Ssh2SftpClient('sftp.example.com', 'user', privateKeyPath: '~/.ssh/id_rsa'));
```

## Базовые операции

```php
// Чтение / Запись
$content = $fs->read('path/to/file.txt');
$fs->write('path/to/file.txt', 'content');

// Удаление / Проверка
$fs->delete('path/to/file.txt');
$exists = $fs->exists('path/to/file.txt');

// Список файлов
$files = $fs->listContents('images/'); // string[]

// Переименование / Копирование
$fs->move('old/path.txt', 'new/path.txt');
$fs->copy('source.txt', 'destination.txt');
```

## Streams (большие файлы)

```php
// Чтение потоком
$stream = $fs->readStream('videos/large-video.mp4');
while (!feof($stream)) {
    echo fread($stream, 8192);
}
fclose($stream);

// Запись потоком
$stream = fopen('/tmp/upload.bin', 'rb');
$fs->writeStream('uploads/upload.bin', $stream);
fclose($stream);
```

## URL-генерация

```php
$url = $fs->url('images/avatar.jpg');
// Local:  /storage/images/avatar.jpg
// S3:     https://my-bucket.s3.us-east-1.amazonaws.com/images/avatar.jpg
// FTP:    ftp://ftp.example.com/images/avatar.jpg
```

## Flysystem-адаптер

```php
use League\Flysystem\Filesystem as FlysystemFilesystem;
use Nextphp\Filesystem\FlysystemFilesystem as NextphpFlysystem;

// Обернуть Flysystem v3 адаптер в Nextphp интерфейс
$flysystem = new FlysystemFilesystem($anyFlysystemAdapter);
$fs = new NextphpFlysystem($flysystem);
```
