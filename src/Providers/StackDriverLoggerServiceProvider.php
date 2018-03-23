<?php

namespace Pagevamp\Providers;

use Google\Cloud\Logging\PsrLogger;
use Illuminate\Support\ServiceProvider;
use Google\Cloud\Logging\LoggingClient;

class StackDriverLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $args = func_get_args();

            // Laravel 5.4 returns a MessageLogged instance only
            if (count($args) == 1) {
                $level = $args[0]->level;
                $message = $args[0]->message;
                $context = $args[0]->context;
            } else {
                $level = $args[0];
                $message = $args[1];
                $context = $args[2];
            }

            if ($message instanceof \ErrorException) {
                return $this->getLogger()->log($level, $message, $context);
            }
            if ($app['google.logger'] instanceof PsrLogger) {
                $app['google.logger']->log($level, $message, $context);
            }
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('google.logger', function () {
            try {
                return $this->getLogger(true);
            } catch (\Exception $error) {
                return app('log');
            }
        });
    }

    protected function getLogger($batchEnabled = false)
    {
        $logging = new LoggingClient($this->getCredentials());

        return $logging->psrLogger($this->getLogName(), [
            'batchEnabled' => $batchEnabled,
        ]);
    }

    protected function getCredentials()
    {
        return config('services.stack_driver_logger.credentials');
    }

    protected function getLogName()
    {
        return config('services.stack_driver_logger.log_name') ? : 'example-log';
    }
}
