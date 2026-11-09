<?php

use App\Http\Controllers\Issues\IssueController;
use Illuminate\Support\Facades\Route;

Route::resource('', IssueController::class)
    ->parameters(['' => 'issue'])
    ->only(['index', 'create', 'show', 'edit']);
