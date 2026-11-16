<?php

use App\Http\Controllers\Users\UsersController;

Route::resource('', UsersController::class)
    ->only(['index']);
