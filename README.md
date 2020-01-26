# Cloudfront@Edge Event to Request Transformer

This small package allows you to transform a Cloudfront@Edge Event into a proper Request-Object

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require christoph-kluge/cloudfront-edge-php-adapter
```

# Usage Example (long running process - one process for multiple requests)

Please take care that your application MAY require adoptions to run in a long-running-process.

Advantages:

* Your app is always warm after ther initial request
* Leverage full potential of opcache

Performance Measurements

This code runs in a production environment api. 
Almost every endpoint responsds in <10ms (server-response time) w/o the usage of memcache/redis/..

```php
#!/opt/bin/php
<?php

use Bref\Runtime\LambdaRuntime;

$appRoot = getenv('LAMBDA_TASK_ROOT');
require $appRoot . '/vendor/autoload.php';

/** @var \App\Extensions\Foundation\Application $app */
$app = require_once $appRoot . '/bootstrap/app.php';

/** @var CloudfrontEdgeHandler $handler */
$handler = $app->make(CloudfrontEdgeHandler::class);

$lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();
while (true) {
    $lambdaRuntime->processNextEvent($handler);
}
```

# Usage (new process per request)
 
```php
#!/opt/bin/php
<?php

$appRoot = getenv('LAMBDA_TASK_ROOT');
require $appRoot . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $appRoot . '/bootstrap/app.php';

lambda($app->make(CloudfrontEdgeHandler::class));
```

# Features

* [x] Support requests with different methods (GET, HEAD, POST, PUT, DELETE, ..)
* [x] Support laravel's dedicated response objects (Response, RedirectResponse, JsonResponse)
* [x] Support post requests from html forms with content-type : x-www-form-urlencoded 
* [x] Support cookies
* [X] Origin custom - Read custom headers
* [X] Origin s3 - Read custom headers 
* [x] Response might send headers as multiple list-items inside the response
* [x] Attachments - Single (`<input name="single"/>`)
* [x] Attachments - Multiple - single named (`<input name="single_1"/>` `<input name="single_2"/>`)
* [x] Attachments - Multiple - array based input (`<input name="file_as_array[]"/>` `<input name="file_as_array[]"/>`)
* [x] Attachments - Multiple - array based with multiple flag (`<input name="files_as_array_multiple[]" multiple/>`)

# TODOs

* [ ] Attachments - Multiple - single named with multiple flag (`<input name="single_with_multiple" multiple/>`)
* [ ] Attachments - Multiple - multi-dimenionsional input name w/o multiple flag (`<input name="myfile[a][b]"/>`)
* [ ] Attachments - Multiple - multi-dimenionsional input name w/ multiple flag (`<input name="myfile[a][b]"multiple/>`)
* [ ] Attachments - Multiple - multi-dimenionsional input name with array w/o multiple flag (`<input name="myfile[a][b][]"/>`)
* [ ] Attachments - Multiple - multi-dimenionsional input name with array w/ multiple flag (`<input name="myfile[a][b][]" multiple/>`)
* [ ] Check POST with different content-types (json, x-www-form-urlencode, ..?)
* [ ] Check 204 responses with JSON why they return "{}" instead of ""
* [ ] Cloudfront MAY send multiple list-items for a single header
* [ ] Origin custom - Origin Protocol Policy (Mid prio - I guess there might be use-cases where SSL is not used)
* [ ] Origin custom - Read different path (Low prio - imo not required for lambda)
* [ ] Origin custom - Ssl protocol (Low prio - imo not required for lambda)
* [ ] Origin s3 - Origin Protocol Policy (Mid prio - I guess there might be use-cases where SSL is not used)
* [ ] Origin s3 - Read different path (Low prio - imo not required for lambda)
* [ ] Origin s3 - Restrict Bucket (Low prio - imo not required for lambda)
