# Raw HTTP Request Factory

A library to create a server request that implements PSR-7 ServerRequestInterface from the raw http request string and vice versa according to the [RFC2616 Section 5](https://www.w3.org/Protocols/RFC/rfc2616-sec5.html).

## Installation

```bash
$ composer require phpstandard/raw-request-factory
```

## Basic Usage

```php
<?php

use Framework\Http\RawRequestFactory;

// Any implementation of the Psr\Http\Message\ServerRequestFactoryInterface
$server_request_factory = new ServerRequestFactory;

// Any implementation of the Psr\Http\Message\StreamFactoryInterface
$stream_factory = new StreamFactory;

// In most cases this will be a server request
// captured from globals (a real http request to the server).
$server_request = $server_request_factory->createServerRequest('POST', 'https://example.com');

$factory = new RawRequestFactory($server_request_factory, $stream_factory);

// Create a raw HTTP request string from the ServerRequestFactoryInterface implementation
$raw_request = $factory->createRawRequest($server_request);

// Create a server request from the raw HTTP request string
$new_server_request = $factory->createServerRequest($raw_request);
```

## Notes

Although RawRequestFactory depends on `ServerRequestFactoryInterface` and `StreamFactoryInterface` implementation of these interfaces are out of scope of this library.

This library wont work properly with `POST` requests with `Content-Type: multipart/form-data; boundary=something` header

## Todo

- Add unit tests
- Improve library to work with `POST` request with `Content-Type: multipart/form-data; boundary=something` header.
