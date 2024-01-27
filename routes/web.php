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

Route::redirect('/', '/admin')->name('login');

Route::get('install/{seed}', function ($seed) {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh --force');
    \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
        'password' => \Illuminate\Support\Facades\Hash::make('Admin2525'),
        'is_active' => 1,
        'is_admin' => 1
    ]);
    if ($seed) {
        \Illuminate\Support\Facades\Artisan::call('db:seed --force');
    }
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return redirect('/');
});

Route::get('storage-link', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return redirect('/');
});

Route::get('excel-download/{file}', function ($file) {
    $file = 'storage/' . $file . ".txt";
    $file_name = 'Inspection_'. now()->format('d-m-Y H-i-s') . '.xlsm';
    return response()->download(public_path($file), $file_name)->deleteFileAfterSend();
})->name('excel-download');

Route::get('/test', function (){
   \App\Jobs\CreateExcel::dispatch(\App\Models\Inspection::find(1));
   return redirect('/');
});


