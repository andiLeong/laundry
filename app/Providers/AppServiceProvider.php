<?php

namespace App\Providers;

use App\Http\Validation\AdminCreateOrderValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        $this->app->bind(AdminCreateOrderValidation::class,function($app){
           return new AdminCreateOrderValidation($app['request']);
        });
    }
}
