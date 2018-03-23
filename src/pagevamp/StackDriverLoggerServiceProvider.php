<?php

namespace App\Providers;

use Google\Cloud\Logging\PsrLogger;
use Illuminate\Support\ServiceProvider;
use Google\Cloud\Logging\LoggingClient;
use Symfony\Component\Debug\Exception\FatalErrorException;

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

            if ($message instanceof FatalErrorException) {
                return $this->getLogger($this->getKeyFile())->log($level, $message, $context);
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
                return $this->getLogger($this->getKeyFile(), true);
            } catch (\Exception $error) {
                return app('log');
            }
        });
    }

    protected function getLogger($keyFile, $batchEnabled = false)
    {
        $logging = new  LoggingClient([
            'keyFile' => $keyFile,
        ]);

        return $logging->psrLogger('vamp-log', [
            'batchEnabled' => $batchEnabled,
        ]);
    }

    protected function getKeyFile()
    {
        return json_decode(trim(env('GC_LOG_SERVICE_CRED'), "'"), 1);
    }
}
