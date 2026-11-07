<?php

namespace Tests\Unit\Notifications\Quotes;

use App\Enums\BoilerManufacturers;
use App\Models\Quote;
use App\Notifications\Quotes\QuoteSubmittedAdmin;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Tests\Unit\UnitTestCase;

class QuoteSubmittedAdminTest extends UnitTestCase
{
    /** @return array<string, array{?BoilerManufacturers, string}> */
    public static function manufacturers(): array
    {
        return [
            'not provided' => [null, 'Neuvedeno'],
            'known manufacturer' => [BoilerManufacturers::DRAZICE, 'Dražice'],
            'unknown manufacturer' => [BoilerManufacturers::UNKNOWN, 'Neznámý'],
        ];
    }

    #[DataProvider('manufacturers')]
    public function test_mail_displays_the_manufacturer_or_a_fallback(?BoilerManufacturers $manufacturer, string $expected): void
    {
        $quote = Quote::factory()->make(['boiler_manufacturer' => $manufacturer]);
        $quote->setRelation('files', new Collection);

        $mail = (new QuoteSubmittedAdmin($quote))->toMail(new stdClass);

        $this->assertContains('Výrobce kotle: ' . $expected, $mail->introLines);
    }
}
