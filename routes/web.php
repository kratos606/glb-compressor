<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ModelController;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/upload', [ModelController::class, 'upload']);
Route::get('/models/{id}', [ModelController::class, 'download']);
