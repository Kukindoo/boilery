<?php

use App\Http\Controllers\Customers\CustomersController;

Route::resource('', CustomersController::class)
    ->parameters(['' => 'customer'])
    ->only(['index', 'show', 'edit']);
