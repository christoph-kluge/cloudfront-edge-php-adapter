<?php

namespace Sikei\CloudfrontEdge\Symfony;

use Symfony\Component\HttpFoundation\Response;

class ResponseFactory
{

    public function toCloudfrontEvent(Response $response): array
    {
        return [
            "status" => $response->getStatusCode(),
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
        foreach ($response->headers->all() as $key => $value) {

            $headers = [];
            foreach ($value as $cookie) {
                $headers[] = [
                    'key' => $key,
                    'value' => (string)$cookie,
                ];
            }

            $cfHeaders[$key] = $headers;
        }
        return $cfHeaders;
    }
}