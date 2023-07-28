<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        return Promotion::query()
            ->select(['id', 'name', 'description', 'start', 'until', 'isolated', 'status'])
            ->filters([
                'name' => [
                    'clause' => 'like',
                ],
            ], $request)
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function show(Promotion $promotion)
    {
        return $promotion;
    }
}
