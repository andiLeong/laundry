<?php

namespace App\Providers;

use App\Http\Validation\AdminCreateBulkOrderValidation;
use App\Http\Validation\AdminCreateOrderValidation;
use App\Http\Validation\AdminCreateOrderWithPromotionValidation;
use App\Http\Validation\OrderValidate;
use App\Models\Promotions\PromotionNotFoundException;
use App\Models\Promotions\UserQualifiedPromotion;
use App\Models\Sms\Contract\Sms as SmsContract;
use App\Models\Sms\Template;
use App\Models\Sms\Twilio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client as TwilioSdk;

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
        $this->app->bind(OrderValidate::class, function ($app) {
            $request = $app['request'];
            if($request->has('promotion_ids')){
                return new AdminCreateOrderWithPromotionValidation($request);
            }
            return new AdminCreateOrderValidation($request);
        });

        $this->app->bind(AdminCreateBulkOrderValidation::class, function ($app) {
            return new AdminCreateBulkOrderValidation(new AdminCreateOrderValidation($app['request']));
        });

        $this->app->singleton(SmsContract::class, function ($app) {
            $config = $app['config'];
            $sid = $config->get('services.twilio.sid');
            $token = $config->get('services.twilio.auth_toke');
            $number = $config->get('services.twilio.number');
            return new Twilio(new TwilioSdk($sid, $token), $number);
        });

        $this->app->singleton(Template::class, function ($app) {
            return new Template();
        });
    }
}
