# Cloudfront@Edge Event to Request Transformer

This small package allows you to transform a Cloudfront@Edge Event into a proper Request-Object

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require christoph-kluge/cloudfront-edge-php-adapter
```

# Usage

```php
<?php declare(strict_types=1);

use App\Extensions\CloudfrontEdge\Laravel\RequestFactory;
use App\Extensions\CloudfrontEdge\Laravel\ResponseFactory;

require __DIR__ . '/../bootstrap/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

/** @var \App\Http\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

/** @var RequestFactory $requestFactory */
$requestFactory = $app->make(RequestFactory::class);

/** @var ResponseFactory $responseFactory */
$responseFactory = $app->make(ResponseFactory::class);

lambda(function (array $event) use ($kernel, $requestFactory, $responseFactory) : array {
    $response = $kernel->handle(
        $request = $requestFactory->fromCloudfrontEvent($event)
    );

    $cfResponse = $responseFactory->toCloudfrontEvent($response);

    $kernel->terminate($request, $response);

    return $cfResponse;
});
```

# TODOs

* [x] Simple GET request
* [x] Simple POST request
* [x] Support laravel's dedicated response objects (Response, RedirectResponse, JsonResponse)
* [x] Support x-www-form-urlencoded form post requests
* [x] Support for cookies
* [ ] Cloudfront MAY send multiple list-items for a single header
* [ ] Response might send headers as multiple list-items inside the response
* [ ] Check POST with different content-types
* [ ] Check POST with attachments (uploaded files)

# License

MIT License

Copyright (c) 2019 Christoph Kluge

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
