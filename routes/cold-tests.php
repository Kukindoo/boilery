<?php

use App\Http\Controllers\Tests\EmailColdTestController;

    Route::post('tests/email',
        EmailColdTestController::class
    )->name('tests.email');
