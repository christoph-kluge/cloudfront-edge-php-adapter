<?php

namespace Sikei\CloudfrontEdge\Unit\Symfony;

use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function test_cookies()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-cookies.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertCount(3, $request->cookies->all());
        $this->assertCount(3, $request->cookie());
        $this->assertSame('MyValue', $request->cookie('MyCookie'));
    }

    public function test_post_with_form_urlencoded_body()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-post-form-urlencoded-body.json'), true);

        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('application/x-www-form-urlencoded', $request->header('content-type'));

        $this->assertSame('john.doe@example.net', $request->input('email'));
        $this->assertSame('john.doe@example.net', $request->input('password'));
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

    public function test_file_upload_single_image()
    {
        // @TODO: take a smaller test-file for this
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-file-upload.json'), true);

        // vendor/symfony/http-foundation/Tests/FileBagTest.php
        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertSame(1, $request->files->count());

        $file = $request->files->get('file');
        $this->assertInstanceOf(UploadedFile::class, $file);

        /** @var UploadedFile $file */
        $this->assertEquals('laravel.jpg', $file->getClientOriginalName());
        $this->assertEquals('image/jpeg', $file->getClientMimeType());
    }

    public function test_file_upload_multiple_images()
    {
        // @TODO: take a smaller test-file for this
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-file-upload-multiple.json'), true);

        // vendor/symfony/http-foundation/Tests/FileBagTest.php
        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertSame(2, $request->files->count());

        // FILE 1
        $jpeg = $request->files->get('jpeg');
        $this->assertInstanceOf(UploadedFile::class, $jpeg);

        /** @var UploadedFile $jpeg */
        $this->assertEquals('laravel.jpg', $jpeg->getClientOriginalName());
        $this->assertEquals('image/jpeg', $jpeg->getClientMimeType());

        // FILE 2
        $svg = $request->files->get('svg');
        $this->assertInstanceOf(UploadedFile::class, $svg);

        /** @var UploadedFile $jpeg */
        $this->assertEquals('logotype.min.svg', $svg->getClientOriginalName());
        $this->assertEquals('application/octet-stream', $svg->getClientMimeType());
    }

    public function test_file_upload_multiple_array()
    {
        $this->markTestSkipped('not yet implemented - need to get cases for files[], files[a][b] and probably files[a][b][]');

        // @TODO: take a smaller test-file for this
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-file-upload-mutliple-mixed.json'), true);

        // vendor/symfony/http-foundation/Tests/FileBagTest.php
        $request = $this->factory->fromCloudfrontEvent($event);

        $this->assertSame(4, $request->files->count());
    }
}