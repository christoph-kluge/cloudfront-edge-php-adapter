<?php

namespace Sikei\CloudfrontEdge\Unit\Laravel;

use Bref\Context\ContextBuilder;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use Sikei\CloudfrontEdge\Tests\Helpers\RequestEventBuilder;

class RequestFactoryTest extends TestCase
{
    private $factory;
    private $context;

    public function setUp(): void
    {
        $this->factory = new RequestFactory(new SymfonyRequestFactory());
        $this->context = (new ContextBuilder())->buildContext();
    }

    public function test_that_transformation_will_return_laravel_request_object()
    {
        $event = RequestEventBuilder::create('/laravel-request', 'GET');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->method());
        $this->assertEquals('/laravel-request', $request->getRequestUri());
    }
}
