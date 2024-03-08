<?php declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use Amp\ClamAV;

echo 'connecting...' . PHP_EOL;

if (ClamAV\ping()) {
    echo 'connected successfully!' . PHP_EOL;
} else {
    echo 'connection failed!' . PHP_EOL;
    return;
}
echo 'running test scan...' . PHP_EOL;

/** @var ClamAV\ScanResult */
$result = ClamAV\scan('/tmp/eicar.com');
echo (string) $result . PHP_EOL;
