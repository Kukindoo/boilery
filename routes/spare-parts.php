<?php

use App\Http\Controllers\SpareParts\SparePartController;

Route::resource('', SparePartController::class)
    ->parameters(['' => 'spare_part'])
    ->only(['index', 'create', 'show', 'edit']);
