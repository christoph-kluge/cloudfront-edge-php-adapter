<?php

namespace Sikei\CloudfrontEdge\Unit\Symfony;

use Sikei\CloudfrontEdge\Symfony\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

        $cfResponse = $this->factory->make($response);

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

        $cfResponse = $this->factory->make($response);

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

    public function test_cookies()
    {
        $response = new Response();
        $response->headers->setCookie(new Cookie('my-cookie', 'my-value'));
        $response->headers->setCookie(new Cookie('my-second-cookie', 'my-second-value'));

        $cfResponse = $this->factory->make($response);

        $this->assertTrue(isset($cfResponse['headers']['set-cookie']));
        $this->assertCount(2, $cfResponse['headers']['set-cookie']);
        $this->assertSame([
            [
                'key' => 'set-cookie',
                'value' => 'my-cookie=my-value; path=/; httponly',
            ],
            [
                'key' => 'set-cookie',
                'value' => 'my-second-cookie=my-second-value; path=/; httponly',
            ],
        ], $cfResponse['headers']['set-cookie']);
    }

    public function test_single_headers_and_multiple_headers_in_a_response()
    {
        $response = new Response();
        $response->headers->set('my-custom-header', 'value');
        $response->headers->setCookie(new Cookie('my-cookie', 'my-value'));
        $response->headers->setCookie(new Cookie('my-second-cookie', 'my-second-value'));

        $cfResponse = $this->factory->make($response);

        // 2 default headers which are always set (Date + Cache-Control) + 2 custom headers (my-custom-header + set-cookies)
        $this->assertCount(2 + 2, $cfResponse['headers']);

        // cookies
        $this->assertTrue(isset($cfResponse['headers']['set-cookie']));
        $this->assertCount(2, $cfResponse['headers']['set-cookie']);
        $this->assertSame([
            [
                'key' => 'set-cookie',
                'value' => 'my-cookie=my-value; path=/; httponly',
            ],
            [
                'key' => 'set-cookie',
                'value' => 'my-second-cookie=my-second-value; path=/; httponly',
            ],
        ], $cfResponse['headers']['set-cookie']);

        // custom-header
        $this->assertTrue(isset($cfResponse['headers']['my-custom-header']));
        $this->assertCount(1, $cfResponse['headers']['my-custom-header']);
        $this->assertSame('value', $cfResponse['headers']['my-custom-header'][0]['value']);
    }

    public function test_response_is_always_a_string()
    {
        // The Lambda function result failed validation: The body is not a string, is not an object, or exceeds the maximum size.
        // https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/http-502-bad-gateway.html

        $response = new Response();

        $cfResponse = $this->factory->make($response);

        $this->assertIsString($cfResponse['body']);
        $this->assertEmpty($cfResponse['body']);
    }

    public function test_response_json_is_always_a_string()
    {
        // The Lambda function result failed validation: The body is not a string, is not an object, or exceeds the maximum size.
        // https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/http-502-bad-gateway.html

        $response = new JsonResponse( null, 204);

        $cfResponse = $this->factory->make($response);

        $this->assertIsString($cfResponse['body']);
        $this->assertSame('{}', $cfResponse['body']); // Check why this is "{}"
    }
}
