<?php

use App\Http\Controllers\Quotes\QuoteAcceptController;
use App\Http\Controllers\Quotes\QuoteController;
use App\Http\Controllers\Quotes\QuotePdfController;

Route::controller(QuoteController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{quote}', 'show')->name('show');
});

Route::post('/{quote}/accept', QuoteAcceptController::class)->name('accept');

Route::post('/{quote}/generate-pdf', QuotePdfController::class)->name('generate-pdf');
