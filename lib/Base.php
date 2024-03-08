<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\Socket\Socket;

abstract class Base
{
    /**
     * Pings the ClamAV daemon.
     *
     */
    public function ping(): bool
    {
        return 'PONG' === $this->command('PING');
    }

    /**
     * Scans a file or directory using the native ClamD `SCAN` command (ClamD must have access to this file!).
     *
     * Stops once a malware has been found.
     *
     *
     */
    public function scan(string $path): ScanResult
    {
        return $this->parseScanOutput($this->command('SCAN ' . $path));
    }

    /**
     * Runs the `VERSION` command.
     *
     * @return string
     */
    public function version()
    {
        return \trim($this->command('VERSION'));
    }

    /**
     * Scans from a stream.
     *
     *
     * @throws \Amp\ClamAV\ClamException May happen while writing to the stream (if the INSTREAM limit has been reached, the errorCode will be `ClamException::INSTREAM_WRITE_EXCEEDED`)
     * @throws \Amp\ByteStream\ClosedException If the socket has been closed
     */
    abstract public function scanFromStream(ReadableStream $stream): ScanResult;

    /**
     * Pipes an InputStream to a ClamD socket by using the `INSTREAM` command.
     *
     * @param \Amp\ByteStream\ReadableStream $stream The stream to pipe
     * @param \Amp\Socket\Socket $socket The destination socket
     *
     * @throws \Amp\ByteStream\ClosedException If the socket has been closed
     * @throws \Amp\ByteStream\StreamException If the writing fails
     */
    protected function pipeStreamScan(ReadableStream $stream, Socket $socket): void
    {
        $socket->write("zINSTREAM\x0");
        while (null !== $chunk = $stream->read()) {
            if (empty($chunk)) {
                continue;
            }
            // The format of the chunk is:
            // '<length><data>' where <length> is the size of the  following
            // data in bytes expressed as a 4 byte unsigned integer in network
            // byte order and <data> is the actual chunk.
            // man: clamd

            // pack the chunk length
            $lengthData = \pack('N', \strlen($chunk));
            $socket->write($lengthData . $chunk);
            $chunk = null;
        }
        $socket->write(\pack('N', 0));
    }

    /**
     * Handles a [StreamException] as a result of [pipeStreamScan] after a
     * [scanFromStream] call.
     *
     * @codeCoverageIgnore
     * @throws \Amp\ClamAV\ClamException
     */
    protected function handleStreamException(StreamException $e, ?string $message): void
    {
        if ($message != null) {
            if (\str_starts_with($message, 'INSTREAM size limit exceeded')) {
                throw new ClamException('INSTREAM size limit exceeded', ClamException::INSTREAM_WRITE_EXCEEDED, $e);
            }

            throw new ClamException($message, ClamException::UNKNOWN, $e);
        }

        throw new ClamException($e->getMessage(), ClamException::UNKNOWN, $e);
    }

    /**
     * Parses the scan's output (of a `SCAN`, `MULTISCAN`, `CONTSCAN`, ... command).
     *
     * @param string $output The unparsed output
     *
     * @throws \Amp\ClamAV\ClamException
     * @throws \Amp\ClamAV\ParseException
     */
    protected function parseScanOutput(string $output): ScanResult
    {
        $output = \trim($output);
        $separatorPos = \strrpos($output, ': ');
        if ($separatorPos === false) {
            throw new ParseException('Could not parse string: ' . $output);
        }
        $separatorLength = 2;
        $filename = \substr($output, 0, $separatorPos);
        $result = \substr($output, $separatorPos + $separatorLength);
        if (empty($filename) || empty($result)) {
            throw new ParseException('Could not parse string: ' . $output);
        }
        // filepath: <virtype> FOUND/OK/ERROR
        if ($result === 'OK') {
            return new ScanResult($filename, false, null);
        }

        if (\str_ends_with($output, ' FOUND')) {
            return new ScanResult($filename, true, \substr($result, 0, \strrpos($result, ' FOUND')));
        }

        if (\str_ends_with($output, ' ERROR')) {
            throw new ClamException(\substr($result, 0, \strrpos($result, ' ERROR')));
        }

        if ($result === 'COMMAND READ TIMED OUT') {
            throw new ClamException('timeout', ClamException::TIMEOUT);
        }

        throw new ClamException('ClamAV sent an invalid or unknown response: ' . $output, ClamException::UNKNOWN);
    }

    /**
     * Executes a command to ClamD and if waitForResponse is true, wait for the response (see different implementation).
     *
     * @param string $command The command to execute
     * @param bool $waitForResponse Wait for the response
     *
     * @return ?string
     */
    abstract protected function command(string $command, bool $waitForResponse = true): ?string;
}
