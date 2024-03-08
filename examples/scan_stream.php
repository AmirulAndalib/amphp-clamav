<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Amp\ClamAV;

echo 'connecting...' . PHP_EOL;

if (ClamAV\ping()) {
    echo 'connected!' . PHP_EOL;
} else {
    echo 'connection failed.' . PHP_EOL;
    return;
}
echo 'running a streamed scan...' . PHP_EOL;

/** @var \Amp\File\File */
$file = \Amp\File\openFile('/tmp/eicar.com', 'r');

/** @var \Amp\ClamAV\ScanResult */
$res = ClamAV\scanFromStream($file);
$file->close(); // always close files to avoid memory leaks
echo (string) $res . PHP_EOL;
