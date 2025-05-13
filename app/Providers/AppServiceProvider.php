<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route; // Import the Route facade

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define the "home" route
        Route::pattern('id', '[0-9]+'); // Define pattern for route parameter {id}

        // Define the routes for the application.
        $this->defineRoutes();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    protected function defineRoutes()
    {
        Route::middleware('web')
             ->group(base_path('routes/web.php'));
    }
}
