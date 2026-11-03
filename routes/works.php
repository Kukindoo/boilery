<?php

use App\Http\Controllers\Works\RequestedWorkFilePreviewController;
use App\Http\Controllers\Works\WorkCancelController;
use App\Http\Controllers\Works\WorkEndController;
use App\Http\Controllers\Works\WorksController;
use App\Http\Controllers\Works\WorkStartController;

Route::resource('', WorksController::class)
    ->parameters(['' => 'work'])
    ->only(['index', 'create', 'show', 'edit']);

Route::get(
    '{work}/files/{file}/preview',
    RequestedWorkFilePreviewController::class
)->name('files.preview');

Route::post('{work}/start', WorkStartController::class)->name('start');

Route::post('{work}/end', WorkEndController::class)->name('end');

Route::post('{work}/cancel', WorkCancelController::class)->name('cancel');
