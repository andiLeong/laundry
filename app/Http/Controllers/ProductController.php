<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
    	if(request()->has('all')){
			return Product::select(['id','price','stock','name'])->orderBy('id', 'desc')->get();
    	}
        return Product::orderBy('id', 'desc')->paginate();
    }
}
