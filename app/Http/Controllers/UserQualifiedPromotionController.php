<?php

namespace App\Http\Controllers;

use App\Models\Promotions\UserQualifiedPromotion;
use App\Models\Service;
use App\Models\User;

class UserQualifiedPromotionController extends Controller
{
    public function index(User $user, Service $service)
    {
        [$isolated, $nonIsolated] = resolve(UserQualifiedPromotion::class, [$user, $service])
            ->setColumns(['id', 'name', 'status', 'start', 'until', 'isolated', 'class', 'discount'])
            ->get()
            ->partition(fn($promotion) => $promotion->isIsolated());

        return [
            'isolated' => $isolated->values(),
            'non-isolated' => $nonIsolated->values(),
        ];
    }
}
