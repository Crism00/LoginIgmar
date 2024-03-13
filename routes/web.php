<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
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

Route::get('/', function () {
    return view('welcome');
})->name('/');
Route::get('/codeVerification', function (){
    return view('auth.enter-code');
})->name('codeVerification')->middleware('RoleValidation:1')->middleware('verified')->middleware('twoFactorTrue');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard2')->middleware('RoleValidation:2') ->middleware('twoFactor');

Route::get('/dashboardAdmin', function () {
    return view('dashboardAdmin');
})->middleware(['auth', 'verified'])->name('dashboard1')->middleware('RoleValidation:1')
    ->middleware('twoFactor');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
})->middleware('twoFactor');

require __DIR__.'/auth.php';
