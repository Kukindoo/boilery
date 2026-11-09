<?php

use App\Http\Controllers\AssemblyActions\AssemblyActionController;

Route::resource('', AssemblyActionController::class)
    ->parameters(['' => 'assembly_action'])
    ->only(['index', 'create', 'show', 'edit']);
