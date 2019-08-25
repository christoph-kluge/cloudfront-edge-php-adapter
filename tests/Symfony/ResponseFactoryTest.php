<?php

namespace Sikei\CloudfrontEdge\Unit\Symfony;

use Illuminate\Http\Response;
use Sikei\CloudfrontEdge\Symfony\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function test_simple_response()
    {
        $now = new \DateTime();

        $response = (new Response('body', 200, []))
            ->setCache([
                'private' => 'no-cache, private',
            ])
            ->setDate($now);

        $cfResponse = $this->factory->toCloudfrontEvent($response);

        $this->assertSame($cfResponse, [
            "status" => 200,
            "headers" => [
                'cache-control' => [[
                    'key' => 'cache-control',
                    'value' => 'private',
                ]],
                'date' => [[
                    'key' => 'date',
                    'value' => $now->format('D, d M Y H:i:s') . ' GMT',
                ]],
            ],
            "body" => 'body',
        ]);
    }

    public function test_different_status_code()
    {
        $now = new \DateTime();

        $response = (new Response('body', 201, []))
            ->setCache([
                'private' => 'no-cache, private',
            ])
            ->setDate($now);

        $cfResponse = $this->factory->toCloudfrontEvent($response);

        $this->assertSame($cfResponse, [
            "status" => 201,
            "headers" => [
                'cache-control' => [[
                    'key' => 'cache-control',
                    'value' => 'private',
                ]],
                'date' => [[
                    'key' => 'date',
                    'value' => $now->format('D, d M Y H:i:s') . ' GMT',
                ]],
            ],
            "body" => 'body',
        ]);
    }
}