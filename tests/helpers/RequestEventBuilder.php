<?php declare(strict_types=1);

namespace Sikei\CloudfrontEdge\Tests\Helpers;

class RequestEventBuilder
{
    private $distributionDomainName = 'aaa111bbb222c3.cloudfront.net';
    private $distributionId = 'EEEEEE9FFFFFF0';
    private $eventType = 'origin-request';
    private $body = [
        'action' => 'read-only',
        'data' => '',
        'encoding' => '',
        'inputTruncated' => false,
    ];
    private $clientIp = '1.2.3.4';
    private $cookies = [];
    private $headers = [];
    private $method = '';
    private $origin = [
        's3' => [
            "authMethod" => "none",
            "customHeaders" => [],
            "domainName" => "some-s3-bucket.s3.amazonaws.com",
            "path" => "",
        ],
    ];
    private $queryString = '';
    private $uri = '/';

    public static function create(string $uri, string $method = 'GET', string $eventType = 'origin-request'): RequestEventBuilder
    {
        return new static($uri, $method, $eventType);
    }

    public function __construct(string $uri, string $method = 'GET', string $eventType = 'origin-request')
    {
        $this->method = $method;
        $this->eventType = $eventType;
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->queryString = parse_url($uri, PHP_URL_QUERY) ?? '';
    }

    public function addHeader(string $name, string $value, bool $replace = false)
    {
        $lowerName = strtolower($name);

        if (!isset($this->headers[$lowerName]) || $replace === true) {
            $this->headers[$lowerName] = [];
        }

        $this->headers[$lowerName][] = [
            'key' => $name,
            'value' => $value,
        ];

        return $this;
    }

    public function setBody(string $body, bool $base64encode = true)
    {
        $this->addHeader('Content-Length', (string) strlen($body));

        $this->body = [
            'action' => 'read-only',
            'data' => $base64encode ? base64_encode($body) : '',
            'encoding' => $base64encode ? 'base64' : '',
            'inputTruncated' => false,
        ];

        return $this;
    }

    public function addCookie(string $name, string $value)
    {
        $this->cookies[$name] = sprintf('%s=%s;',
            $name,
            urlencode($value) // cookies are passed as urlencoded strings
        );
        return $this;
    }

    public function toArray(): array
    {
        $this->addCookies();

        return [
            'Records' => [
                [
                    'cf' => [
                        'config' => [
                            'distributionDomainName' => $this->distributionDomainName,
                            'distributionId' => $this->distributionId,
                            'eventType' => $this->eventType,
                        ],
                        'request' => [
                            'body' => $this->body,
                            'clientIp' => $this->clientIp,
                            'headers' => $this->headers,
                            'method' => $this->method,
                            'origin' => $this->origin,
                            'querystring' => $this->queryString,
                            'uri' => $this->uri,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function addCookies(): void
    {
        if (empty($this->cookies)) {
            return;
        }

        $this->addHeader('Cookie', implode(' ', $this->cookies), true);
    }
}