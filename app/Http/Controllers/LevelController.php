<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LevelController extends Controller
{
    //
}
Route::get('/', function(){
    return view('welcome');
});

Route::get('/level', [LevelController::class, 'index']);
