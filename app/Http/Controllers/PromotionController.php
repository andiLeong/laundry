<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * @var array|string[]
     */
    private array $promotionsColumns;

    /**
     * @var array|string[]
     */
    private array $promotionColumns;

    public function __construct()
    {
        $noColumnRestriction = auth()->check() && !auth()->user()->customer();
        $this->promotionsColumns = $noColumnRestriction ? ['slug', 'name', 'description', 'start', 'until', 'isolated'] : ['slug', 'name', 'description','image','thumbnail'];
        $this->promotionColumns = $noColumnRestriction ? ['*'] : ['name','slug','description','discount','isolated','start','until','image'];
    }

    public function index(Request $request)
    {
        return Promotion::query()
            ->enabled()
            ->select($this->promotionsColumns)
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
            ->select($this->promotionColumns)
            ->enabled()
            ->where('slug',$slug)
            ->first();

        if(is_null($promotion)){
            abort(404,'Promotion not found');
        }
        return $promotion;
    }
}
