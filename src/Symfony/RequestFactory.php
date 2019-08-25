<?php

namespace Sikei\CloudfrontEdge\Symfony;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory
{

    public function fromCloudfrontEvent(array $event): Request
    {
        $cfConfig = $event['Records'][0]['cf']['config'];
        $cfRequest = $event['Records'][0]['cf']['request'];

        Request::enableHttpMethodParameterOverride();

        $request = Request::create(
            $this->getUri($cfConfig, $cfRequest),
            $this->getMethod($cfRequest),
            $this->getParameters($cfRequest),
            [],
            [],
            $this->getServer($cfRequest),
            $this->getContent($cfRequest)
        );

        return $request;
    }

    private function getUri(array $cfConfig, array $cfRequest): string
    {
        $domain = $cfConfig['distributionDomainName'];
        if (isset($cfRequest['headers']['host'][0]['value'])) { // optional host header
            $domain = $cfRequest['headers']['host'][0]['value'];
        }

        $isSecure = true;
        $scheme = 'http://';
        if ($isSecure) {
            $scheme = 'https://';
        }

        $query = '';
        if (strlen($cfRequest['querystring']) > 0) {
            $query = '?' . $cfRequest['querystring'];
        }

        return $scheme . $domain . $cfRequest['uri'] . $query;
    }

    private function getMethod(array $cfRequest): string
    {
        return $cfRequest['method'];
    }

    private function getContent(array $cfRequest)
    {
        $content = null;
        if (!empty($cfRequest['body']['data']) && $cfRequest['body']['encoding'] === 'base64') {
            $content = base64_decode($cfRequest['body']['data']);
        }
        return $content;
    }

    private function getServer(array $cfRequest): array
    {
        $server = [];

        // add clientIp to server
        $server['REMOTE_ADDR'] = $cfRequest['clientIp'];

        // https
        $server['HTTPS'] = 'on';

        // Add headers to server
        foreach ($cfRequest['headers'] as $key => $values) {
            $origKey = strtoupper($values[0]['key']);
            $origKey = str_replace('-', '_', $origKey);

            // CONTENT_* are not prefixed with HTTP_
            if (0 === strpos($origKey, 'CONTENT_')) {
                $server[$origKey] = $values[0]['value'];
            } else {
                $server['HTTP_' . $origKey] = $values[0]['value'];
            }
        }
        return $server;
    }

    private function getParameters(array $cfRequest): array
    {
        if (!in_array($this->getMethod($cfRequest), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return [];
        }

        if (!isset($cfRequest['headers']['content-type'][0]['value'])
            || $cfRequest['headers']['content-type'][0]['value'] !== 'application/x-www-form-urlencoded') { // check
            return [];
        }

        parse_str($this->getContent($cfRequest), $data);

        return $data;
    }
}