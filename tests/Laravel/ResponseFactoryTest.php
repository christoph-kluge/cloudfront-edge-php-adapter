<?php

namespace Sikei\CloudfrontEdge\Unit\Laravel;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Laravel\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function test_normal_response()
    {
        $response = new Response('my-body', 201);

        $cfResponse = $this->factory->make($response);

        // status code
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame(201, $cfResponse['status']);

        // body
        $this->assertSame('my-body', $cfResponse['body']);
    }

    public function test_redirect_response_301()
    {
        $response = new RedirectResponse('/my-redirect-url', 301);

        $cfResponse = $this->factory->make($response);

        // status code
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame(301, $cfResponse['status']);

        // header
        $this->assertTrue(isset($cfResponse['headers']['location'][0]['value']));
        $this->assertStringContainsString('/my-redirect-url', $cfResponse['headers']['location'][0]['value']);
    }

    public function test_redirect_response_302()
    {
        $response = new RedirectResponse('/my-redirect-url', 302);

        $cfResponse = $this->factory->make($response);

        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame(302, $cfResponse['status']);
    }

    public function test_json_response()
    {
        $response = new JsonResponse(['a' => 'b']);

        $cfResponse = $this->factory->make($response);

        // status
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame(200, $cfResponse['status']);

        // body
        $this->assertSame(json_encode(['a' => 'b']), $cfResponse['body']);

        // header
        $this->assertTrue(isset($cfResponse['headers']['content-type'][0]['value']));
        $this->assertStringContainsString('application/json', $cfResponse['headers']['content-type'][0]['value']);
    }
}
