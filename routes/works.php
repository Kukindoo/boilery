<?php

use App\Http\Controllers\Works\RequestedWorkFilePreviewController;
use App\Http\Controllers\Works\WorksController;

Route::resource('', WorksController::class)
    ->parameters(['' => 'work'])
    ->only(['index', 'create', 'show', 'edit']);

Route::get(
    '{work}/files/{file}/preview',
    RequestedWorkFilePreviewController::class
)->name('works.files.preview');
