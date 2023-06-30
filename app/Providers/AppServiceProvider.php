<?php

namespace App\Providers;

use App\Http\Validation\AdminCreateOrderValidation;
use App\Models\Sms\Contract\Sms as SmsContract;
use App\Models\Sms\Sms;
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

        $this->app->singleton(SmsContract::class,function($app){
            return new Sms();
        });
    }
}
