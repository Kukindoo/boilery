<?php

use App\Http\Controllers\Quotes\QuoteAcceptController;
use App\Http\Controllers\Quotes\QuoteController;
use App\Http\Controllers\Quotes\QuotePrintController;
use App\Http\Controllers\Quotes\RequestedQuoteFilePreviewController;

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

Route::post('/{quote}/accept', QuoteAcceptController::class)->name('accept');
