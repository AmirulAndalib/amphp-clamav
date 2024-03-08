# amphp-clamav

![license: MIT](https://img.shields.io/badge/license-MIT-blue)

An asynchronous ClamAV wrapper written with amphp/socket

## Migrating from v1.x.x

The updated v2.0.0 brought some breaking changes because of the changes in the underlying `amphp/amp`. These underlying changes give a great boost to the asynchronous event loop, being it now based on `Fibers` instead of `Generators`.

Mostly you only need to remove the `yield` keyword before any calls to the library's asynchronous function. Learn more on [Amp's Upgrade Guide](https://amphp.org/upgrade).

## Installing

```
composer require pato05/amphp-clamav
```

## Examples

Ping and scan of a file/directory: [`examples/scan.php`](https://github.com/Pato05/amphp-clamav/blob/main/examples/scan.php)

Scanning from a `ReadableStream` (in this case a `File` instance which implements `ReadableStream`): [`examples/scan_stream.php`](https://github.com/Pato05/amphp-clamav/blob/main/examples/scan_stream.php)

## Using a TCP/IP socket instead

If you want to use a TCP/IP socket instead of a UNIX one, you should use the `ClamAV\clamav()` function prior to any other call, or just use a custom `ClamAV` instance:

```php
\Amp\ClamAV\clamav('tcp://IP:PORT'); // to access it statically
// or
$clamav = new \Amp\ClamAV\ClamAV('tcp://IP:PORT');
```

Be aware that TCP/IP sockets may be slightly slower than UNIX ones.

## Using MULTISCAN

MULTISCAN is supported but can only be used on non-session instances (due to a ClamAV limitation).

MULTISCAN allows you to make a multithreaded scan.

```php
$result = \Amp\ClamAV\multiScan('FILEPATH');
```

## Differences between running a session and without

Sessions run on the same socket connection, while non-session instances will reconnect to the socket for each command. The library supports both, it's up to you deciding which to use.

Instantiating a session is pretty straight forward, just use the `ClamAV::session()` method like this:

```php
$clamSession = \Amp\ClamAV\session();
```

Though you MUST end every session by using the method `Session::end()`:

```php
$clamSession->end();
```

Be aware that in a session you can only execute ONE COMMAND AT A TIME, therefore, if you want to run more than one command in parallel, use the main `ClamAV` class instead.

Multiple `Session`s can also be instantiated.
