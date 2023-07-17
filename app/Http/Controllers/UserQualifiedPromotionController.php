<?php

namespace App\Http\Controllers;

use App\Models\Promotions\UserQualifiedPromotion;
use App\Models\Service;
use App\Models\User;

class UserQualifiedPromotionController extends Controller
{
    public function index(User $user, Service $service)
    {
        $qualifiedPromotion = new UserQualifiedPromotion($user, $service);
        $qualifiedPromotion->setColumns(['id', 'name', 'status', 'start', 'until', 'isolated', 'class','discount']);

        try {
            [$isolated, $nonIsolated] = $qualifiedPromotion
                ->get()
                ->partition(fn($promotion) => $promotion->isIsolated());

            return [
                'isolated' => $isolated->values(),
                'non-isolated' => $nonIsolated->values(),
            ];

        } catch (\Exception $e) {
            abort(503, $e->getMessage());
        }
    }
}
