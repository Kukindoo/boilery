<?php

namespace Tests\Feature\Quote;

use App\Livewire\Quotes\CustomerQuoteRequestPage;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomerQuoteRequestPageTest extends FeatureTestCase
{
    /** @return array<string, array{string, string, string}> */
    public static function accountNames(): array
    {
        return [
            'single word' => ['Admin', 'Admin', ''],
            'two parts' => ['Jan Novák', 'Jan', 'Novák'],
            'multiple surname parts' => ['Jan Novák Svoboda', 'Jan', 'Novák Svoboda'],
            'extra whitespace' => ["  Jan \t Novák  \n Svoboda  ", 'Jan', 'Novák Svoboda'],
            'single word with padding' => ['  Admin  ', 'Admin', ''],
            'whitespace only' => ['   ', '', ''],
        ];
    }

    #[DataProvider('accountNames')]
    public function test_authenticated_form_prefills_normalized_names(string $name, string $firstName, string $lastName): void
    {
        $user = User::factory()->create(['name' => $name]);

        Livewire::actingAs($user)->test(CustomerQuoteRequestPage::class)
            ->assertSet('form.firstName', $firstName)
            ->assertSet('form.lastName', $lastName)
            ->assertSet('form.email', $user->email)
            ->assertSet('form.phone', $user->phone)
            ->assertSet('form.address', $user->address);
    }
}
