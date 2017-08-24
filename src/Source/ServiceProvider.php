<?php

namespace {{ NAMESPACE }};

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class {{ UCNAME }}ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $router->aliasMiddleware('{{ NAME }}', \{{ NAMESPACE }}\Middleware\{{ UCNAME }}Middleware::class);

        $this->publishes([
            __DIR__.'/Config/{{ NAME }}.php' => config_path('{{ NAME }}.php'),
        ], '{{ NAME }}_config');

        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        $this->loadTranslationsFrom(__DIR__ . '/Translations', '{{ NAME }}');

        $this->publishes([
            __DIR__ . '/Translations' => resource_path('lang/vendor/{{ NAME }}'),
        ]);

        $this->loadViewsFrom(__DIR__ . '/Views', '{{ NAME }}');

        $this->publishes([
            __DIR__ . '/Views' => resource_path('views/vendor/{{ NAME }}'),
        ]);

        $this->publishes([
            __DIR__ . '/Assets' => public_path('vendor/{{ NAME }}'),
        ], '{{ NAME }}_assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \{{ NAMESPACE }}\Commands\{{ UCNAME }}Command::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/{{ NAME }}.php', '{{ NAME }}'
        );
    }
}
