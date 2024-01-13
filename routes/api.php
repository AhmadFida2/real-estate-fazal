<?php

use App\Http\Resources\InspectionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/inspection/{id}', function (string $id) {
    return new \App\Http\Resources\InspectionResource(\App\Models\Inspection::findOrFail($id));
});

Route::get('/inspections', function () {
    return new \App\Http\Resources\InspectionCollection(\App\Models\Inspection::all());
});
