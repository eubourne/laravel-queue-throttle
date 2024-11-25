<p style="text-align: center"><img src="/assets/laravel-queue-throttle-card.jpg" alt="Laravel Queue Throttle"></p>

# Laravel Queue Throttle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eubourne/laravel-queue-throttle.svg?style=flat-square)](https://packagist.org/packages/eubourne/laravel-queue-throttle)

**Laravel Queue Throttle**  is a package designed to manage and throttle the processing of jobs in Laravel queues.
It enables you to set rate limits on your queues, preventing system overloads by controlling job execution rates.

- [Features](#features)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Sharing Rate Limits](#sharing-rate-limits)
- [Optimization](#optimization)
- [Logging](#logging)
- [Testing](#testing)
  - [Queue Status](#queue-status)
  - [Fill Queue](#fill-queue)
- [License](#license)
- [Contributing](#contributing)
- [Contact](#contact)
---

## Features
- **Rate limiting:** Define limits on how many jobs can be processed within a specified time frame for individual queues.
- **Shareable limits:** Apply a single rate limit across multiple queues for flexible control.
---

## Installation

To install the package, run:

```bash
composer require eubourne/laravel-queue-throttle
```

This package leverages Laravel's [Laravels package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) feature,
so no manual service provider registration is required.

## Basic Usage

To set a rate limit for a queue, update the `throttle` configuration in your `config/queue.php` file:

```php
/*
|--------------------------------------------------------------------------
| Rate Limits
|--------------------------------------------------------------------------
|
| Here you can set rate limits for specific queues to control the processing rate.
|
*/

'throttle' => [
    'mail' => [
        'allows' => 10,
        'every' => 60,
    ]
],
```
In this example, the `mail` queue is limited to processing 10 jobs every 60 seconds. Other queues will remain
unrestricted by default.

## Sharing Rate Limits

To share a rate limit across multiple queues, separate the queue names with a colon (`:`):

```php
'throttle' => [
    'mail:notifications' => [
        'allows' => 10,
        'every' => 60,
    ]
],
```
Here, the `mail` and `notifications` queues share the same limit. For instance, if 8 jobs are processed
from the `mail` queue, only 2 jobs can be processed from the `notifications` queue within the same 60-second interval.

## Optimization
Improve performance and reduce configuration resolution overhead by caching rate limit configurations:
```bash
php artisan queue:cache
```

To clear cached configurations, run:
```bash 
php artisan queue:clear-cache
```

Additionally, standard Laravel optimization commands are supported:
```bash
php artisan optimize
php artisan optimize:clear
```

View the current rate limit configuration with:
```bash
php artisan queue:throttle
```
---

## Logging
To debug rate-limiting activities, set the `throttle_logger` configuration in your `config/queue.php`:
```php
/*
|--------------------------------------------------------------------------
| Rate Limits Logger
|--------------------------------------------------------------------------
|
| Configure logging channel for the queue throttler. If not set then logging
| will be disabled.
|
*/

'throttle_logger' => env('QUEUE_THROTTLE_LOGGER', null),
```
Specify a logging channel to monitor and debug throttling operations. If left unset, logging will be disabled.
---

## Testing
The package includes helper Artisan commands to test queue functionality.

### Queue Status
Check if a queue is functioning and processing jobs: 
```bash
php artisan queue:test {queue}
```
This command dispatches a test job to the specified queue and verifies successful processing.

### Fill Queue
Fill a queue with test jobs to simulate workload and observe processing behavior:
```bash
php artisan queue:fill {count} {--queue=} 
```

For example, to test rate limiting on the `mail` and `notifications` queues:
```bash
php artisan queue:fill 20 --queue=mail
php artisan queue:fill 20 --queue=notifications
```

Monitor the processing of these queues using Laravel's built-in monitoring command:
```bash
php artisan queue:monitor --queue=mail,notifications
```

## License
This package is open-source and available for free under the [MIT license](http://opensource.org/licenses/MIT).

## Contributing
Feel free to submit issues or pull requests to help improve this package.

## Contact
For more information or support, please reach out via GitHub or email.
