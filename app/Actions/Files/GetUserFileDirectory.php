<?php

namespace App\Actions\Files;

use Illuminate\Validation\ValidationException;

class GetUserFileDirectory
{
    public function handle(?int $userId): string
    {
        if ($userId === null) {
            throw ValidationException::withMessages([
                'user_id' => 'Před uložením souboru musí být přiřazen zákazník.',
            ]);
        }

        return str_replace('{user_id}', (string) $userId, config('app.files.user_save_directory'));
    }
}
