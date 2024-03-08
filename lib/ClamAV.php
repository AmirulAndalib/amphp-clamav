<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\Socket\Socket;

use function Amp\call;

class ClamAV extends Base
{
    const DEFAULT_SOCK_URI = 'unix:///run/clamav/clamd.ctl';

    /**
     * Constructs the class.
     *
     * @param string $sockuri The socket uri (`unix://PATH` or `tcp://IP:PORT`)
     */
    public function __construct(private $sockuri = self::DEFAULT_SOCK_URI)
    {
    }

    /**
     * Initiates a new ClamAV session
     * Note: you MUST call `Session::end()` once you are done.
     *
     */
    public function session(): Session
    {
        /** @var Socket */
        $socket = \Amp\Socket\connect($this->sockuri);

        return Session::fromSocket($socket);
    }

    /**
     * Runs a continue scan that stops after the entire file has been checked.
     *
     *
     * @return ScanResult[]
     */
    public function continueScan(string $path): array
    {
        $output = \trim($this->command('CONTSCAN ' . $path));
        return \array_map([$this, 'parseScanOutput'], \array_filter(\explode("\n", $output), fn ($val) => !empty($val)));
    }

    /**
     * Runs a multithreaded ClamAV scan (using the `MULTISCAN` command).
     *
     * @param string $path The file or directory's path
     *
     */
    public function multiScan(string $path): ScanResult
    {
        return $this->parseScanOutput($this->command('MULTISCAN ' . $path));
    }

    /** @inheritdoc */
    public function scanFromStream(ReadableStream $stream): ScanResult
    {
        $socket = $this->getSocket();
        try {
            $this->pipeStreamScan($stream, $socket);
        } catch (StreamException $e) {
            $this->handleStreamException($e, $socket->isClosed() ? null : $socket->read());
        }
        return $this->parseScanOutput($socket->read());
    }

    /** @inheritdoc */
    protected function command(string $command, bool $waitForResponse = true): ?string
    {
        $socket = $this->getSocket();
        $socket->write('z' . $command . "\x0");
        if ($waitForResponse) {
            return \trim($socket->read());
        }
    }

    /**
     * Gets a new socket (to execute a new command).
     *
     */
    protected function getSocket(): Socket
    {
        return \Amp\Socket\connect($this->sockuri);
    }
}
