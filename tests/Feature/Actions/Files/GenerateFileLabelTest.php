<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class GenerateFileLabelTest extends FeatureTestCase
{
    /** @return array<string, array{string, int, FileTypes, ?string, string}> */
    public static function labels(): array
    {
        return [
            'quote summary' => ['Jiří Novák', 42, FileTypes::QUOTE_SUMMARY, 'pdf', 'Jiri_Novak_42_shrnuti_poptavky.pdf'],
            'service protocol' => ['Anna Nováková-Svobodová', 9, FileTypes::SERVICE_PROTOCOL, 'pdf', 'Anna_Novakova_Svobodova_9_servisni_protokol.pdf'],
            'image attachment' => ['Jan Novák', 3, FileTypes::BOILER_LABEL, 'png', 'Jan_Novak_3_stitek.png'],
            'without extension' => ['Jiří Novák', 42, FileTypes::QUOTE_SUMMARY, null, 'Jiri_Novak_42_shrnuti_poptavky'],
            'empty extension' => ['Jiří Novák', 42, FileTypes::QUOTE_SUMMARY, '', 'Jiri_Novak_42_shrnuti_poptavky'],
        ];
    }

    #[DataProvider('labels')]
    public function test_generates_an_ascii_label_from_the_customer_name_and_record_id(
        string $name,
        int $id,
        FileTypes $fileType,
        ?string $extension,
        string $expectedLabel,
    ): void {
        $user = User::factory()->make(['name' => $name]);

        $label = app(GenerateFileLabel::class)->handle($user, $id, $fileType, $extension);

        $this->assertSame($expectedLabel, $label);
    }

    public function test_extension_is_optional(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);

        $label = app(GenerateFileLabel::class)->handle($user, 9, FileTypes::SERVICE_PROTOCOL);

        $this->assertSame('Jan_Novak_9_servisni_protokol', $label);
    }
}
