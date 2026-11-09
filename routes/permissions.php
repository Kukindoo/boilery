<?php

use App\Http\Controllers\Permissions\PermissionController;

Route::resource('', PermissionController::class)
    ->parameters(['' => 'permission'])
    ->only(['index', 'show']);
