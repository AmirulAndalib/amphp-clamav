<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\PHPUnit\AsyncTestCase;

class ClamAVTest extends AsyncTestCase
{
    private function copyTmp(string $from)
    {
        $to = '/tmp/' . \basename($from) . \uniqid();
        $fromFile = \Amp\File\openFile($from, 'r');
        $toFile = \Amp\File\openFile($to, 'w');
        \Amp\ByteStream\pipe($fromFile, $toFile);

        return $to;
    }

    private function runTestOn(string $target, bool $assertIsInfected, ?string $assertMalwareType)
    {
        $this->assertTrue(\Amp\File\exists($target));

        $session = session();

        $copyTarget = $this->copyTmp($target);
        $shouldBe = new ScanResult(
            $copyTarget,
            $assertIsInfected,
            $assertMalwareType
        );
        $scan = scan($copyTarget);
        $scan2 = multiScan($copyTarget);
        $scan3 = $session->scan($copyTarget);
        $this->assertObjectEquals($scan, $shouldBe);
        $this->assertObjectEquals($scan2, $shouldBe);
        $this->assertObjectEquals($scan3, $shouldBe);

        $scan4 = continueScan($copyTarget);
        $this->assertSame(\count($scan4), 1);
        $this->assertObjectEquals($scan4[0], $shouldBe);
        \Amp\File\deleteFile($copyTarget);

        // try stream scan
        $shouldBe = new ScanResult(
            'stream',
            $assertIsInfected,
            $assertMalwareType
        );
        $file = \Amp\File\openFile($target, 'r');
        $scan = scanFromStream($file);
        // reset stream position
        $file->seek(0);
        $scan2 = $session->scanFromStream($file);
        $this->assertObjectEquals($scan, $shouldBe);
        $this->assertObjectEquals($scan2, $shouldBe);
        $file->close();
        $session->end();
    }

    public function test(): void
    {
        $this->assertTrue(ping());

        echo 'TEST RUNNING ON CLAMAV: ' . version() . PHP_EOL;
    }

    public function testEicar(): void
    {
        $target = __DIR__ . '/eicar.com';
        $this->runTestOn(
            target: $target,
            assertIsInfected: true,
            assertMalwareType: 'Win.Test.EICAR_HDB-1'
        );
    }

    public function testNormalFile(): void
    {
        $target = __DIR__ . '/harmless.txt';
        $this->runTestOn(
            target: $target,
            assertIsInfected: false,
            assertMalwareType: null
        );
    }
}
