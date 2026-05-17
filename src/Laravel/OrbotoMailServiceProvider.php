<?php

declare(strict_types=1);

namespace Orboto\Mail\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Orboto\Mail\OrbotoMail;

/**
 * Laravel auto-discovery service-provider for the Orboto Mail SDK.
 *
 * Binds `Orboto\Mail\OrbotoMail` as a singleton on the container so
 * Laravel apps can `app(OrbotoMail::class)->send(...)` or use the
 * facade alias `OrbotoMail::send(...)` without any manual wiring.
 *
 * Config: `config/orboto-mail.php` — publish with
 *   `php artisan vendor:publish --tag=orboto-mail-config`
 */
final class OrbotoMailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/orboto-mail.php', 'orboto-mail');

        $this->app->singleton(OrbotoMail::class, function (Container $app): OrbotoMail {
            /** @var array<string,mixed> $config */
            $config = $app->make('config')->get('orboto-mail', []);
            return new OrbotoMail([
                'apiKey' => $config['api_key'] ?? null,
                'baseUrl' => $config['base_url'] ?? null,
                'timeout' => $config['timeout'] ?? null,
                'maxRetries' => $config['max_retries'] ?? null,
            ]);
        });
    }

    public function boot(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/config/orboto-mail.php' => config_path('orboto-mail.php'),
            ], 'orboto-mail-config');
        }
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [OrbotoMail::class];
    }
}
