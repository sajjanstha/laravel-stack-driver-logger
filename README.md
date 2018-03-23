# Laravel Stack Driver Logger

Google Cloud Stack Driver error monitoring integration for Laravel projects.
This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Stack Driver, as well as some other helpful information such as 'environment', 'server', and 'session'.


Installation
------------

Install using composer:

```
composer require pagevamp/laravel-stack-driver-logger
```

Add the service provider to the `'providers'` array in `config/app.php`:

```php
Pagevamp\Providers\StackDriverLoggerServiceProvider::class,
```
    
If you only want to enable Stack Driver reporting for certain environments you can conditionally load the service provider in your `AppServiceProvider`:

```php
    public function register()
    {
        if ($this->app->environment('production')) {
            $this->app->register(\Pagevamp\Providers\StackDriverLoggerServiceProvider::class);
        }
    }
```
Configuration
-------------

This package supports configuration through the services configuration file located in `config/services.php`. All configuration variables will be directly passed to Stack Driver:

```php
'stack_driver_logger' => [
    'credentials' => [
        'keyFile' => json_decode(trim(env('GC_LOG_SERVICE_CRED'), "'"), 1),
    ],
    'log_name' => env('GC_LOG_NAME', 'builder-log'),
],
```
Usage
-----

To automatically monitor exceptions, simply use the `Log` facade in your error handler in `app/Exceptions/Handler.php`:

```php
public function report(Exception $exception)
{
    \Log::error($exception); //Stack Driver
    parent::report($exception);
}
```

Your other log messages will also be sent to Stack Driver:

```php
\Log::debug('Here is some debug information');
```

*NOTE*: Fatal exceptions will always be sent to Stack Driver.

### Context informaton

You can pass user information as context like this:

```php
\Log::error('Something went wrong', [
    'person' => ['id' => 123, 'username' => 'John Doe', 'email' => 'john@doe.com']
]);
```

Or pass some extra information:

```php
\Log::warning('Something went wrong', [
    'download_size' => 3432425235
]);
```
