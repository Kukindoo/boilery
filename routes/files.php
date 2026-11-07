<?php

use App\Http\Controllers\Files\FilePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('{modelType}/{model}/{file}/preview', FilePreviewController::class)
    ->whereIn('modelType', ['quote', 'work'])
    ->name('preview');
