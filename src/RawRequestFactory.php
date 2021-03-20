<?php

namespace Framework\Http;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Provides methods to convert ServerRequestInterface to string and vice versa
 * according to the W3 RFC2616 protocol
 * 
 * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html
 */
class RawRequestFactory
{
    /** @var ServerRequestFactoryInterface $serverRequestFactory */
    private $serverRequestFactory;

    /** $@var StreamFactoryInterface $streamFactory */
    private $streamFactory;

    /** @var string $crlf Carriage Return and Line Feed */
    private $crlf = "\r\n";

    /** @var string $sp Space */
    private $sp = " ";

    public function __construct(
        ServerRequestFactoryInterface $server_request_factory,
        StreamFactoryInterface $stream_factory
    ) {
        $this->serverRequestFactory = $server_request_factory;
        $this->streamFactory = $stream_factory;
    }

    /**
     * Create a raw http request string from the ServerRequestInterface 
     * according to the W3 RFC2616 protocol
     * 
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html
     * 
     * @param ServerRequestInterface $req
     * @return string
     */
    public function createRawRequest(ServerRequestInterface $req): string
    {
        $request_line =
            $req->getMethod()
            . $this->sp . $req->getUri()
            . $this->sp . 'HTTP/' . $req->getProtocolVersion()
            . $this->crlf;

        $headers = [];
        foreach ($req->getHeaders() as $name => $values) {
            $headers[] =  $name . ':' . $req->getHeaderLine($name);
        }

        $headers = implode($this->crlf, $headers) . $this->crlf;

        $body = $req->getBody()->getContents();

        $raw = $request_line . $headers . $this->crlf . $body;

        return $raw;
    }

    /**
     * Create a ServerRequest from raw http request. 
     * 
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html
     *
     * @param string $raw A valid raw HTTP request
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $raw): ServerRequestInterface
    {
        $parts = explode($this->crlf, $raw);

        $request_line = array_shift($parts);
        $body = array_pop($parts);
        $headers = $parts;

        list($method, $uri, $http_version) = array_pad(
            explode($this->sp, $request_line, 3),
            3,
            null
        );

        list(, $protocol_version) = array_pad(
            explode("/", $http_version ?: '', 2),
            2,
            null
        );

        $req =
            $this->serverRequestFactory->createServerRequest($method, $uri)
            ->withProtocolVersion($protocol_version)
            ->withBody($this->streamFactory->createStream($body));

        foreach ($headers as $line) {
            if (strpos($line, ":") === false) {
                continue;
            }

            list($name, $value) = explode(":", $line, 2);
            $values = explode(",", $value);

            foreach ($values as $val) {
                $req = $req->withAddedHeader($name, $val);
            }
        }

        if ($body) {
            $content_type = $req->getHeaderLine('Content-Type');
            if (
                $method == 'POST'
                && in_array($content_type, ['application/x-www-form-urlencoded', 'multipart/form-data'])
            ) {
                parse_str($body, $parsed_body);
                $req = $req->withParsedBody($parsed_body);
            }
        }

        return $req;
    }
}
