<?php

use Illuminate\Support\Facades\Route;

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

Route::redirect('/','/admin')->name('login');

Route::get('install', function (){
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh --force');
    \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
        'password' => \Illuminate\Support\Facades\Hash::make('Admin2525'),
        'is_active' => 1,
        'is_admin' => 1
    ]);
    return redirect('/');
});


