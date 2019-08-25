<?php

namespace Sikei\CloudfrontEdge\Unit\Laravel;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;

class RequestFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new RequestFactory(new SymfonyRequestFactory());
    }

    public function test_that_transformation_will_return_laravel_request_object()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-method-get.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->method());
    }
}