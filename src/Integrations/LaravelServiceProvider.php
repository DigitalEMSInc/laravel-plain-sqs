<?php

namespace Dusterio\PlainSqs\Integrations;

use Dusterio\PlainSqs\Sqs\Connector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Throwable;

/**
 * Class CustomQueueServiceProvider
 * @package App\Providers
 */
class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sqs-plain.php' => config_path('sqs-plain.php')
        ]);

        Queue::after(function (JobProcessed $event) {
            try {
                $event->job->delete();
            } catch (Throwable $exception) {
                if (strpos(get_class($exception), 'SqsException') !== false
                    && strpos($exception->getMessage(), 'The receipt handle') !== false
                    && strpos($exception->getMessage(), 'is not valid') !== false
                ) {
                    return;
                } else {
                    throw $exception;
                }
            }
        });
    }

    /**
     * @return void
     */
    public function register()
    {
         $this->app->booted(function () {
            $this->app['queue']->extend('sqs-plain', function () {
                return new Connector();
            });
        });
    }
}
