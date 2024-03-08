<?php declare(strict_types=1);

namespace Amp\ClamAV;

use Amp\ByteStream\ReadableStream;
use Revolt\EventLoop;

const LOOP_STATE_IDENTIFIER = ClamAV::class;

/**
 * Get the application-wide `ClamAV` instance.
 *
 */
function clamav(string $sockuri = ClamAV::DEFAULT_SOCK_URI): ClamAV
{
    static $map;
    $map ??= new \WeakMap();

    $loop = EventLoop::getDriver();
    /** @var ClamAV */

    return $map[$loop] ??= new ClamAV($sockuri);
}

/**
 * Pings the ClamAV daemon.
 *
 */
function ping(): bool
{
    return clamav()->ping();
}

/**
 * Scans a file or directory using the native ClamD `SCAN` command (ClamD must have access to this file!).
 *
 * Stops once a malware has been found.
 *
 *
 */
function scan(string $path): ScanResult
{
    return clamav()->scan($path);
}

/**
 * Runs a multithreaded ClamAV scan (using the `MULTISCAN` command).
 *
 * @param string $path The file or directory's path
 *
 */
function multiScan(string $path): ScanResult
{
    return clamav()->multiScan($path);
}

/**
 * Runs a continue scan that stops after the entire file has been checked.
 *
 *
 * @return \Amp\ClamAV\ScanResult[]
 */
function continueScan(string $path): array
{
    return clamav()->continueScan($path);
}

/**
 * Runs the `VERSION` command.
 *
 */
function version(): string
{
    return clamav()->version();
}

/**
 * Scans from a stream.
 *
 *
 * @throws \Amp\ClamAV\ClamException May happen while writing to the stream (if the INSTREAM limit has been reached, the errorCode will be `ClamException::INSTREAM_WRITE_EXCEEDED`)
 * @throws \Amp\ByteStream\ClosedException If the socket has been closed
 */
function scanFromStream(ReadableStream $stream): ScanResult
{
    return clamav()->scanFromStream($stream);
}

/**
 * Initiates a new ClamAV session
 * Note: you MUST call `Session::end()` once you are done.
 *
 */
function session(): Session
{
    return clamav()->session();
}
