<?php

use App\Http\Controllers\Quotes\QuoteAcceptController;
use App\Http\Controllers\Quotes\QuoteController;
use App\Http\Controllers\Quotes\QuotePdfController;
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

Route::post('/{quote}/accept', QuoteAcceptController::class)->name('accept');

Route::post('/{quote}/generate-pdf', QuotePdfController::class)->name('generate-pdf');
