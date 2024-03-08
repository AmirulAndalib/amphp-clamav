<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\PHPUnit\AsyncTestCase;

use function Amp\delay;

class ClamAVErrorTest extends AsyncTestCase
{

    public function testINSTREAMLimit(): void
    {
        $this->expectException(ClamException::class);
        $this->expectExceptionCode(ClamException::INSTREAM_WRITE_EXCEEDED);
        $this->expectExceptionMessage('INSTREAM size limit exceeded');

        $stream = \Amp\File\openFile('/dev/zero', 'r');
        scanFromStream($stream);
    }

    // public function testTimeout(): void
    // {
    //     $this->expectException(ClamException::class);
    //     $this->expectExceptionCode(ClamException::TIMEOUT);
    //     $this->expectExceptionMessage('timeout');
    //
    //     // to get this error, we're gonna provide a stream which never outputs any data
    //     $stream = new \Amp\ByteStream\ReadableIterableStream((function() {
    //         // a 300s delay should trigger a TIMEOUT?
    //         delay(300);
    //         yield '';
    //     })());
    //
    //     scanFromStream($stream);
    // }

}
