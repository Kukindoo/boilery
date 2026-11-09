<?php

use App\Http\Controllers\Roles\RoleController;

Route::resource('', RoleController::class)
    ->parameters(['' => 'role'])
    ->only(['index', 'show']);
