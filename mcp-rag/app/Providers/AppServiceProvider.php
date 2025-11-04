<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar repositorios
        $this->app->bind(
            \App\Contracts\DocumentRepositoryInterface::class,
            \App\Repositories\DocumentRepository::class
        );

        $this->app->bind(
            \App\Contracts\FragmentRepositoryInterface::class,
            \App\Repositories\FragmentRepository::class
        );

        // Registrar cliente de OpenAI
        $this->app->singleton(\OpenAI\Client::class, function ($app) {
            return \OpenAI::client(config('services.openai.api_key'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
