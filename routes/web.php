<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPdf\Facades\Pdf;


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
    \App\Models\User::create([
        'name' => 'Inspector',
        'email' => 'test@test.com',
        'password' => \Illuminate\Support\Facades\Hash::make('Test2525'),
        'is_active' => 1,
        'is_admin' => 0
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
    $file = 'storage/' . $file . ".xlsx";
    if (file_exists(public_path($file))) {
        return response()->download(public_path($file))->deleteFileAfterSend();
    } else {
        abort(404);
    }

})->name('excel-download');

Route::get('invoice/{id}', function ($id) {
    $assignment = \App\Models\Assignment::find($id);
    Pdf::view('invoice',compact('assignment'))->save(public_path('invoice.pdf'));
    dd('A');
});



