<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        User::create([
            'name' => 'Radomír Závacký',
            'email' => 'bojlery@seznam.cz',
            'password' => bcrypt(Str::password()),
        ]);
    }
};
