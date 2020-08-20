<?php

namespace Sikei\CloudfrontEdge\Unit\Symfony;

use Bref\Context\ContextBuilder;
use Sikei\CloudfrontEdge\Laravel\RequestFactory;
use Sikei\CloudfrontEdge\Symfony\RequestFactory as SymfonyRequestFactory;
use PHPUnit\Framework\TestCase;
use Sikei\CloudfrontEdge\Tests\Helpers\RequestEventBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RequestFactoryTest extends TestCase
{
    private $factory;
    private $context;

    public function setUp(): void
    {
        $this->factory = new RequestFactory(new SymfonyRequestFactory());
        $this->context = (new ContextBuilder())->buildContext();
    }

    public function test_get_request()
    {
        $event = RequestEventBuilder::create('/test-get-request', 'GET');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals('GET', $request->method());
        $this->assertEquals('test-get-request', $request->path());
        $this->assertEquals('/test-get-request', $request->getRequestUri());
    }

    public function test_get_querystring_single_values()
    {
        $event = RequestEventBuilder::create('/test-query-string?limit=20&page=1');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals(20, $request->get('limit'));
        $this->assertEquals(20, $request->post('limit'));
        $this->assertEquals(20, $request->input('limit'));

        $this->assertEquals(1, $request->get('page'));
    }

    public function test_post()
    {
        $event = RequestEventBuilder::create('/test-post-request', 'POST');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('test-post-request', $request->path());
        $this->assertEquals('/test-post-request', $request->getRequestUri());
    }

    public function test_cookies()
    {
        $event = RequestEventBuilder::create('/test-cookie-request')
            ->addCookie('cookie-1', 'value-1')
            ->addCookie('cookie-2', 'value-2');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertCount(2, $request->cookies->all());
        $this->assertCount(2, $request->cookie());

        $this->assertTrue($request->cookies->has('cookie-1'));
        $this->assertSame('value-1', $request->cookies->get('cookie-1'));
        $this->assertSame('value-1', $request->cookie('cookie-1'));

        $this->assertTrue($request->cookies->has('cookie-2'));
        $this->assertSame('value-2', $request->cookies->get('cookie-2'));
        $this->assertSame('value-2', $request->cookie('cookie-2'));
    }

    public function test_post_with_form_urlencoded_body()
    {
        $body = [
            'email' => 'john.doe@example.net',
            'password' => 'john.doe@example.net',
        ];

        $event = RequestEventBuilder::create('/test-some-form-post', 'POST')
            ->addHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->setBody(http_build_str($body));

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('/test-some-form-post', $request->getRequestUri());
        $this->assertEquals('application/x-www-form-urlencoded', $request->header('content-type'));

        // whole body
        $this->assertSame($body, $request->input());
        $this->assertSame($body, $request->all());

        $this->assertSame('john.doe@example.net', $request->get('email'));
        $this->assertSame('john.doe@example.net', $request->input('email'));
        $this->assertSame('john.doe@example.net', $request->post('password'));
    }

    public function test_post_with_json()
    {
        $body = [
            "name" => "John Doe",
            "email" => "john.doe@example.org",
            "password" => "johns-secure-password",
        ];

        $event = RequestEventBuilder::create('/test-some-json-post', 'POST')
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($body));

        $request = $this->factory->make($event->toArray(), $this->context);

        // method and header evaluation
        $this->assertEquals('POST', $request->method());
        $this->assertEquals('/test-some-json-post', $request->getRequestUri());

        // whole json
        $this->assertTrue($request->isJson());
        $this->assertSame($body, $request->input());
        $this->assertSame($body, $request->all());

        // single keys in json
        $this->assertSame($body['email'], $request->get('email'));
        $this->assertSame($body['email'], $request->input('email'));
        $this->assertSame($body['email'], $request->post('email'));
    }

    public function test_headers_single_values()
    {
        $event = RequestEventBuilder::create('/test-cookie-request')
            ->addHeader('header-1', 'value-1')
            ->addHeader('header-2', 'value-2')
            ->addHeader('header-3', 'value-3');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals('value-1', $request->headers->get('header-1')); // lowercase
        $this->assertEquals('value-1', $request->header('header-1')); // lowercase

        $this->assertEquals('value-2', $request->headers->get('Header-2')); // lettercase
        $this->assertEquals('value-2', $request->header('Header-2')); // lettercase

        $this->assertEquals('value-3', $request->headers->get('HEADER-3')); // uppercase
        $this->assertEquals('value-3', $request->header('HEADER-3')); // uppercase
    }


    public function test_origin_headers_will_override_client_headers()
    {
        $event = RequestEventBuilder::create('/test-cookie-request')
            ->addHeader('header-1', 'value-1')
            ->addOriginHeader('header-1', 'value-1-new');

        $request = $this->factory->make($event->toArray(), $this->context);

        $this->assertEquals('value-1-new', $request->headers->get('header-1')); // lowercase
        $this->assertEquals('value-1-new', $request->header('header-1')); // lowercase
    }

    public function test_file_upload_single_image()
    {
        // @TODO: take a smaller test-file for this
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-file-upload.json'), true);

        // vendor/symfony/http-foundation/Tests/FileBagTest.php
        $request = $this->factory->make($event, $this->context);

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
        $request = $this->factory->make($event, $this->context);

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
        $request = $this->factory->make($event, $this->context);

        $this->assertSame(4, $request->files->count());
    }

    public function test_file_upload_mixed_with_form_fields()
    {
        // @TODO: take a smaller test-file for this
        $event = json_decode(file_get_contents(__DIR__ . '/../files/test-file-upload-mutliple-mixed.json'), true);

        $request = $this->factory->make($event, $this->context);

        // test uploads
        $this->assertSame(4, $request->files->count());

        // single named form inputs
        // <input type="file" name="file1"/>
        // <input type="file" name="file2"/>
        $this->assertInstanceOf(UploadedFile::class, $request->files->get('file1'));
        $this->assertInstanceOf(UploadedFile::class, $request->files->get('file2'));

        // array based form inputs - example:
        // <input type="file" name="files[]"/>
        // <input type="file" name="files[]"/>
        $this->assertIsArray($request->files->get('files_multiple'));
        $this->assertCount(2, $request->files->get('files_multiple'));
        $this->assertInstanceOf(UploadedFile::class, $request->files->get('files_multiple')[0]);

        // array based form inputs with multiple key - example:
        // <input type="file" name="files[]" multiple />
        $this->assertIsArray($request->files->get('files'));
        $this->assertCount(2, $request->files->get('files'));
        $this->assertInstanceOf(UploadedFile::class, $request->files->get('files')[0]);

        $this->markTestIncomplete('input fields are not yet parsed correctly');

        // now test the form inputs - example:
        // <input type="text" name="firstname" value="john">
        // <input type="text" name="lastname" value="doe">
        $this->assertSame('john', $request->input('firstname'));
        $this->assertSame('doe', $request->input('lastname'));
    }
}
