<?php

namespace App\Providers;

use App\Http\Validation\AdminCreateOrderValidation;
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
        $this->app->bind(AdminCreateOrderValidation::class, function ($app) {
            return new AdminCreateOrderValidation($app['request']);
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

        $this->app->bind(UserQualifiedPromotion::class, function ($app, $args) {
            if (count($args) === 3) {
                $e = array_pop($args);
            } else {
                $e = new PromotionNotFoundException();
            }
            [$user, $service] = $args;
            return new UserQualifiedPromotion($user, $service, $e);
        });
    }
}
