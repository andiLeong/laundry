<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return ['message' => 'hows your day'];
    return view('welcome');
});

Route::get('/image', function () {
    return view('image');
});


Route::post('/image', function (Request $request) {

    $validator = Validator::make($request->all(), [
        'image.*' => 'image|max:2048',
        'image' => 'required|array|max:2',
    ]);

    if ($validator->fails()) {
        dd($validator->errors());
    }


//    $files = $request->allFiles();
    $files = $request->allFiles()['image'];

    $paths = [];
    foreach ($files as $file){
        $paths[] = $res = $file->store('orderrrr');
    }

    dump($files);
    dd($paths);
    dd($request->all());
});
