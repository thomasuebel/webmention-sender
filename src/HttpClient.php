<?php

declare(strict_types=1);

namespace WebmentionSender;

use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\HttpException;

final class HttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly string $userAgent,
        private readonly int $timeout,
        private readonly int $connectTimeout = 5,
    ) {}

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     * @throws HttpException
     */
    public function get(string $url): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_HEADER         => true,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new HttpException(sprintf('GET %s failed: %s', $url, $error));
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status'  => $statusCode,
            'headers' => $this->parseHeaders(substr($response, 0, $headerSize)),
            'body'    => substr($response, $headerSize),
        ];
    }

    /**
     * @param array<string, string> $fields
     * @return array{status: int}
     * @throws HttpException
     */
    public function post(string $url, array $fields): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new HttpException(sprintf('POST %s failed: %s', $url, $error));
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $statusCode];
    }

    /**
     * Parses raw HTTP headers into a lowercase-keyed map.
     * Duplicate header names (e.g. multiple Link headers) are joined with ', '
     * so that comma-separated parsing downstream sees them as a single value.
     *
     * @return array<string, string>
     */
    private function parseHeaders(string $raw): array
    {
        $headers = [];

        foreach (explode("\r\n", $raw) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $key = strtolower(trim($name));
            $headers[$key] = isset($headers[$key])
                ? $headers[$key] . ', ' . trim($value)
                : trim($value);
        }

        return $headers;
    }
}
