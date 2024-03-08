<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Promise;
use Amp\Socket\Socket;

use function Amp\async;

class Session extends Base
{
    private Socket $socket;
    private array $deferreds = []; // $i -> \Amp\DeferredFuture
    private int $reqId = 1;

    private function __construct()
    {
    }

    /**
     * Makes a Session instance from a socket (shouldn't be used as part of the public API, use ClamAV::session() instead!).
     *
     * @internal
     *
     *
     */
    public static function fromSocket(Socket $socket): self
    {
        $instance = new self;
        $instance->socket = $socket;
        $instance->command('IDSESSION', waitForResponse: false);
        $instance->readLoop();
        return $instance;
    }

    /** @inheritdoc */
    protected function command(string $command, bool $waitForResponse = true): ?string
    {
        $this->socket->write('z' . $command . "\x0");
        if ($waitForResponse) {
            return $this->commandResponseFuture($this->reqId++)->await();
        }

        return null;
    }

    /**
     * Gets or creates a command response promise (that will be later resolved by the readLoop).
     *
     * @param int $reqId The request's id (an auto-increment integer, which will be used by ClamD to identify this request)
     *
     * @return \Amp\Future<string>
     */
    protected function commandResponseFuture(int $reqId): Future
    {
        if (isset($this->deferreds[$reqId])) {
            return $this->deferreds[$reqId];
        }
        $deferred = new DeferredFuture;
        $this->deferreds[$reqId] = $deferred;
        return $deferred->getFuture();
    }

    /**
     * A read loop for the ClamD socket (given that it might send responses unordered).
     *
     * @return \Amp\Future<never>
     */
    protected function readLoop()
    {
        return async(function () {
            $chunk = '';
            // read from the socket
            while (null !== $chunk = $this->socket->read()) {
                // split the message (ex: "1: PONG")
                $parts = \explode(' ', $chunk, 2);
                $message = \trim($parts[1]);
                $id = (int) \substr($parts[0], 0, \strpos($parts[0], ':'));
                if (isset($this->deferreds[$id])) {
                    /** @var DeferredFuture */
                    $deferred = $this->deferreds[$id];
                    // resolve the enqueued request
                    $deferred->complete($message);
                    unset($this->deferreds[$id]);
                }
            }
        });
    }

    /**
     * Ends this session.
     *
     */
    public function end(): void
    {
        $this->command('END', waitForResponse: false);
        $this->socket->end();
    }

    /** @inheritdoc */
    public function scanFromStream(ReadableStream $stream): ScanResult
    {
        $future = $this->commandResponseFuture($this->reqId++);
        try {
            $this->pipeStreamScan($stream, $this->socket);
        } catch (StreamException $e) {
            $this->handleStreamException(
                $e,
                $this->socket->isClosed() ? null : $future->await(),
            );
        }

        return $this->parseScanOutput($future->await());
    }
}
