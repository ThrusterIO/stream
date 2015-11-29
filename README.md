# Stream Component

[![Latest Version](https://img.shields.io/github/release/ThrusterIO/stream.svg?style=flat-square)]
(https://github.com/ThrusterIO/stream/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)]
(LICENSE)
[![Build Status](https://img.shields.io/travis/ThrusterIO/stream.svg?style=flat-square)]
(https://travis-ci.org/ThrusterIO/stream)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ThrusterIO/stream.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/stream)
[![Quality Score](https://img.shields.io/scrutinizer/g/ThrusterIO/stream.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/stream)
[![Total Downloads](https://img.shields.io/packagist/dt/thruster/stream.svg?style=flat-square)]
(https://packagist.org/packages/thruster/stream)

[![Email](https://img.shields.io/badge/email-team@thruster.io-blue.svg?style=flat-square)]
(mailto:team@thruster.io)

The Thruster Stream Component.

Basic readable and writable stream interfaces that support piping.

In order to make the event loop easier to use, this component introduces the concept of streams. They are very similar to the streams found in PHP itself, but have an interface more suited for async I/O.

Mainly it provides interfaces for readable and writable streams, plus a file descriptor based implementation with an in-memory write buffer.


## Install

Via Composer

``` bash
$ composer require thruster/stream
```


## Readable Streams

### EventEmitter Events

* `data`: Emitted whenever data was read from the source.
* `end`: Emitted when the source has reached the `eof`.
* `error`: Emitted when an error occurs.
* `close`: Emitted when the connection is closed.

### Methods

* `isReadable()`: Check if the stream is still in a state allowing it to be
  read from. It becomes unreadable when the connection ends, closes or an
  error occurs.
* `pause()`: Remove the data source file descriptor from the event loop. This
  allows you to throttle incoming data.
* `resume()`: Re-attach the data source after a `pause()`.
* `pipe(WritableStreamInterface $dest, array $options = [])`: Pipe this
  readable stream into a writable stream. Automatically sends all incoming
  data to the destination. Automatically throttles based on what the
  destination can handle.

## Writable Streams

### EventEmitter Events

* `drain`: Emitted if the write buffer became full previously and is now ready
  to accept more data.
* `error`: Emitted whenever an error occurs.
* `close`: Emitted whenever the connection is closed.
* `pipe`: Emitted whenever a readable stream is `pipe()`d into this stream.

### Methods

* `isWritable()`: Check if the stream can still be written to. It cannot be
  written to after an error or when it is closed.
* `write($data)`: Write some data into the stream. If the stream cannot handle
  it, it should buffer the data or emit and `error` event. If the internal
  buffer is full after adding `$data`, `write` should return false, indicating
  that the caller should stop sending data until the buffer `drain`s.
* `end($data = null)`: Optionally write some final data to the stream, empty
  the buffer, then close it.


## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.
