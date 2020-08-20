<?php

namespace Sikei\CloudfrontEdge\Unit\Laravel;

use Bref\Context\ContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Laravel\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    private $factory;
    private $context;
    private $event;

    public function setUp(): void
    {
        $this->factory = new ResponseFactory();
        $this->context = (new ContextBuilder())->buildContext();
        $this->event = [];
    }

    public function test_status_code_is_string_becuase_cf_requires_it()
    {
        $response = new Response('my-body', 201);

        $cfResponse = $this->factory->make($response, $this->context, $this->event);

        // status code
        $this->arrayHasKey('status', $cfResponse);
        $this->assertIsString($cfResponse['status']);
    }

    public function test_normal_response()
    {
        $response = new Response('my-body', 201);

        $cfResponse = $this->factory->make($response, $this->context, $this->event);

        // status code
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame('201', $cfResponse['status']);

        // body
        $this->assertSame('my-body', $cfResponse['body']);
    }

    public function test_redirect_response_301()
    {
        $response = new RedirectResponse('/my-redirect-url', 301);

        $cfResponse = $this->factory->make($response, $this->context, $this->event);

        // status code
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame('301', $cfResponse['status']);

        // header
        $this->assertTrue(isset($cfResponse['headers']['location'][0]['value']));
        $this->assertStringContainsString('/my-redirect-url', $cfResponse['headers']['location'][0]['value']);
    }

    public function test_redirect_response_302()
    {
        $response = new RedirectResponse('/my-redirect-url', 302);

        $cfResponse = $this->factory->make($response, $this->context, $this->event);

        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame('302', $cfResponse['status']);
    }

    public function test_json_response()
    {
        $response = new JsonResponse(['a' => 'b']);

        $cfResponse = $this->factory->make($response, $this->context, $this->event);

        // status
        $this->arrayHasKey('status', $cfResponse);
        $this->assertSame('200', $cfResponse['status']);

        // body
        $this->assertSame(json_encode(['a' => 'b']), $cfResponse['body']);

        // header
        $this->assertTrue(isset($cfResponse['headers']['content-type'][0]['value']));
        $this->assertStringContainsString('application/json', $cfResponse['headers']['content-type'][0]['value']);
    }
}
