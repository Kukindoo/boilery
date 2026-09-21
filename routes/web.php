<?php

use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuotePrintController;
use App\Livewire\Quotes\RequestedQuoteFilePreviewController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::name('quotes.')->prefix('quotes')->group(function () {
        Route::controller(QuoteController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{quote}', 'show')->name('show');
        });

        Route::get(
            '/{quote}/files/{file}/preview',
            RequestedQuoteFilePreviewController::class
        )->name('files.preview');

        Route::controller(QuotePrintController::class)->group(function () {
            Route::get('/{quote}/print', 'show')->name('print');
        });
    });

});

require __DIR__ . '/auth.php';
