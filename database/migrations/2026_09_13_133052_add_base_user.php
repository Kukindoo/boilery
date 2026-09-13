<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;


return new class extends Migration {
    public function up(): void
    {
        User::create([
            'name' => 'Radomír Závacký',
            'email' => 'bojlery@seznam.cz',
            'password' => bcrypt('vjhdbkv$%@$@buofsd'),
        ]);
    }
};
