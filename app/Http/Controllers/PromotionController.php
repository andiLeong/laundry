<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $noColumnRestriction = auth()->check() && !auth()->user()->isCustomer();
        return Promotion::query()
            ->enabled()
            ->select($noColumnRestriction ? [
                'id',
                'slug',
                'name',
                'description',
                'start',
                'until',
                'isolated'
            ] : ['slug', 'name', 'description', 'image', 'thumbnail'])
            ->filters([
                'name' => [
                    'clause' => 'like',
                ],
            ], $request)
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function show($slug)
    {
        $promotion = Promotion::query()
            ->select([
                'name',
                'slug',
                'description',
                'discount',
                'isolated',
                'start',
                'until',
                'image'
            ])
            ->enabled()
            ->where('slug', $slug)
            ->first();

        if (is_null($promotion)) {
            abort(404, 'Promotion not found');
        }
        return $promotion;
    }
}
