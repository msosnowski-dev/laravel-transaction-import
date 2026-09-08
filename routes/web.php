<?php

use Illuminate\Support\Facades\Route;
use App\Models\Import;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function(){

    dd(Import::all());
});
