<?php

use App\Http\Controllers\Tests\EmailColdTestController;

Route::post('email',
    EmailColdTestController::class
)->name('email');
