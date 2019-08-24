<?php

namespace Sikei\CloudfrontEdge\Laravel;

use Illuminate\Http\Response;

class ResponseFactory
{

    public function toCloudfrontEvent(Response $response): array
    {
        return [
            "status" => $response->getStatusCode(),
            "headers" => $this->getHeaders($response),
            "body" => $response->getContent(),
        ];
    }

    /**
     * @param Response $response
     * @return array
     */
    private function getHeaders(Response $response): array
    {
        $headers = [];
        foreach ($response->headers->all() as $key => $value) {
            $headers[$key] = [[
                'key' => $key,
                'value' => (string)$value[0],
            ]];
        }
        return $headers;
    }
}