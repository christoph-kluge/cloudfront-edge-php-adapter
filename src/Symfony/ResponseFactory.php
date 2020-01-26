<?php

namespace Sikei\CloudfrontEdge\Symfony;

use Symfony\Component\HttpFoundation\Response;

class ResponseFactory
{

    public function make(Response $response): array
    {
        return [
            "status" => (string)$response->getStatusCode(),
            "headers" => $this->getHeaders($response),
            "body" => (string)$response->getContent(),
        ];
    }

    /**
     * @param Response $response
     * @return array
     */
    private function getHeaders(Response $response): array
    {
        $cfHeaders = [];
        foreach ($response->headers->all() as $headerName => $headerItems) {

            $items = [];
            foreach ($headerItems as $headerValue) {
                $items[] = [
                    'key' => strtolower($headerName),
                    'value' => (string)$headerValue,
                ];
            }

            $cfHeaders[strtolower($headerName)] = $items;
        }
        return $cfHeaders;
    }
}
