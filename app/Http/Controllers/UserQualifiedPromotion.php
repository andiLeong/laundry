<?php

namespace App\Http\Controllers;

use App\Models\QualifiedPromotion;
use App\Models\Service;
use App\Models\User;

class UserQualifiedPromotion extends Controller
{
    public function index(User $user, Service $service)
    {
        $qualifiedPromotion = new QualifiedPromotion($user, $service);
        $qualifiedPromotion->setColumns(['id', 'name', 'status', 'start', 'until', 'isolated', 'class']);

        try {
            [$isolated, $nonIsolated] = $qualifiedPromotion
                ->get()
                ->partition(fn($promotion) => $promotion->isIsolated());

            return [
                'isolated' => $isolated,
                'non-isolated' => $nonIsolated,
            ];

        } catch (\Exception $e) {
            abort(503, $e->getMessage());
        }
    }
}
