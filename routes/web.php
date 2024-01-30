<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('setup')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('', \App\Livewire\Auth::class)->middleware('guest')->name('auth');
        Route::post('logout', function () {
            auth()->logout();

            return redirect('/');
        })->middleware('auth')->name('auth.logout');
    });

    Route::middleware('auth')->group(function () {
        Route::redirect('', 'apps');

        Route::get('account', \App\Livewire\Account::class)->name('account');

        Route::get('apps', \App\Livewire\Apps\Index::class)->name('apps.index');
        Route::get('apps/{app}', \App\Livewire\Apps\Show::class)->name('apps.show');
        Route::get('apps/{app}/edit', \App\Livewire\Apps\Edit::class)->name('apps.edit');

        Route::get('log', \App\Livewire\Log::class)->middleware('can:administrate')->name('log');

        Route::get('users', \App\Livewire\Users\Index::class)->name('users.index');
    });
});

Route::get('setup', \App\Livewire\Setup::class)->middleware(['guest', 'not-setup'])->name('setup');
