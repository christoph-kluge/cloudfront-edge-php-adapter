<?php

namespace Sikei\CloudfrontEdge\Unit\Laravel;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use Sikei\CloudfrontEdge\Tests\Helpers\RequestEventBuilder;

class RequestFactoryTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = new RequestFactory(new SymfonyRequestFactory());
    }

    public function test_that_transformation_will_return_laravel_request_object()
    {
        $event = RequestEventBuilder::create('/laravel-request', 'GET');

        $request = $this->factory->make($event->toArray());

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->method());
        $this->assertEquals('/laravel-request', $request->getRequestUri());
    }
}
