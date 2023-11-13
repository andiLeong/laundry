<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function index()
    {
         return Cache::remember(
             'services',
             now()->addDays(30),
             fn() => Service::select(['id','name','description','price'])->get()
         );
    }
}
