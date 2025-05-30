# test-pool

![Continuous Integration](https://github.com/Reactphp-parallel/test-pool/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/React-parallel/test-pool/v/stable.png)](https://packagist.org/packages/React-parallel/test-pool)
[![Total Downloads](https://poser.pugx.org/React-parallel/test-pool/downloads.png)](https://packagist.org/packages/React-parallel/test-pool)
[![Code Coverage](https://scrutinizer-ci.com/g/Reactphp-parallel/test-pool/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Reactphp-parallel/test-pool/?branch=master)
[![Type Coverage](https://shepherd.dev/github/Reactphp-parallel/test-pool/coverage.svg)](https://shepherd.dev/github/Reactphp-parallel/test-pool)
[![License](https://poser.pugx.org/React-parallel/test-pool/license.png)](https://packagist.org/packages/React-parallel/test-pool)

Pool meant for used in unit tests

## Install ##

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require react-parallel/test-pool
```

## Usage ##

The following example will spin up a thread with a 1 second TTL clean up policy. Meaning that threads are kept around
for 1 second waiting for something to do before closed. It then runs a closure in the thread that will wait for one
second before returning an message. Upon receiving that message the mean thread will echo out that message before
closing the pool;

```php
use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\test\Direct;

$test = new Direct();
$test->run(function () {
    sleep(1);

    return 'Hoi!';
})->then(function (string $message) use ($test) {
    echo $message, PHP_EOL;
    $test->close();
});
```

## License ##

Copyright 2025 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
