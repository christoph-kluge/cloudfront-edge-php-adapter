<?php

namespace Sikei\CloudfrontEdge\Unit\Symfony;

use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new RequestFactory(new SymfonyRequestFactory());
    }

    public function test_get()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-method-get.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertEquals('GET', $request->method());
    }

    public function test_post()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-method-post.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertEquals('POST', $request->method());
    }

    public function test_delete()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-method-post-with-json-body.json'), true);
        $body = [
            "name" => "John Doe",
            "email" => "john.doe@example.org",
            "password" => "johns-secure-password",
        ];

        $request = $this->factory->fromCloudfrontEvent($event);

        // method and header evaluation
        $this->assertEquals('POST', $request->method());
        $this->assertTrue($request->isJson());

        // whole json
        $this->assertSame($body, $request->input());
        $this->assertSame($body, $request->all());

        // single keys in json
        $this->assertSame($body['email'], $request->get('email'));
        $this->assertSame($body['email'], $request->input('email'));
    }

    public function test_headers_single_values()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-headers.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertEquals('no-cache', $request->header('cache-control')); // lowercase
        $this->assertEquals('test-api.example.net', $request->header('Host')); // lettercase
        $this->assertEquals('Amazon CloudFront', $request->header('USER-AGENT')); // uppercase
    }

    public function test_querystring_single_values()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-query-string-single-values.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertEquals(20, $request->get('limit'));
        $this->assertEquals(1, $request->get('page'));

    }
}