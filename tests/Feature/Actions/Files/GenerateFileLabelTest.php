<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class GenerateFileLabelTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 8, 9, 42, 0, 'UTC'));
        Str::createRandomStringsUsingSequence(['ABCD', 'ABCD', 'ABCD']);
        $this->beforeApplicationDestroyed(fn () => Str::createRandomStringsNormally());
    }

    /** @return array<string, array{string, FileTypes, ?string, string}> */
    public static function labels(): array
    {
        return [
            'quote summary' => ['Jiří Novák', FileTypes::QUOTE_SUMMARY, 'pdf', '2026-11-08_10-42_Jiri_Novak_shrnuti_poptavky_abcd.pdf'],
            'service protocol' => ['Anna Nováková-Svobodová', FileTypes::SERVICE_PROTOCOL, 'pdf', '2026-11-08_10-42_Anna_Novakova_Svobodova_servisni_protokol_abcd.pdf'],
            'image attachment' => ['Jan Novák', FileTypes::BOILER_LABEL, 'png', '2026-11-08_10-42_Jan_Novak_stitek_abcd.png'],
            'without extension' => ['Jiří Novák', FileTypes::QUOTE_SUMMARY, null, '2026-11-08_10-42_Jiri_Novak_shrnuti_poptavky_abcd'],
            'empty extension' => ['Jiří Novák', FileTypes::QUOTE_SUMMARY, '', '2026-11-08_10-42_Jiri_Novak_shrnuti_poptavky_abcd'],
        ];
    }

    #[DataProvider('labels')]
    public function test_generates_an_ascii_label_with_prague_timestamp_type_and_four_alphanumeric_characters(
        string $name,
        FileTypes $fileType,
        ?string $extension,
        string $expectedLabel,
    ): void {
        $user = User::factory()->make(['name' => $name]);

        $label = app(GenerateFileLabel::class)->handle($user, $fileType, new File(['extension' => $extension]));

        $this->assertSame($expectedLabel, $label);
    }

    public function test_uses_the_unsaved_file_models_extension(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);
        $file = new File(['original_name' => 'receipt.txt', 'extension' => 'pdf']);

        $label = app(GenerateFileLabel::class)->handle($user, FileTypes::RECEIPT, $file);

        $this->assertSame('2026-11-08_10-42_Jan_Novak_kupni_smlouva_abcd.pdf', $label);
    }

    public function test_extension_is_optional(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);

        $label = app(GenerateFileLabel::class)->handle($user, FileTypes::SERVICE_PROTOCOL, new File());

        $this->assertSame('2026-11-08_10-42_Jan_Novak_servisni_protokol_abcd', $label);
    }

    public function test_suffix_accepts_digits_and_lowercases_letters(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);
        Str::createRandomStringsUsingSequence(['A1B2', 'A1B2', 'A1B2']);

        $label = app(GenerateFileLabel::class)->handle($user, FileTypes::RECEIPT, new File(['extension' => 'pdf']));

        $this->assertSame('2026-11-08_10-42_Jan_Novak_kupni_smlouva_a1b2.pdf', $label);
    }

    public function test_returns_the_first_available_candidate_after_a_collision(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);
        File::factory()->create(['label' => '2026-11-08_10-42_Jan_Novak_kupni_smlouva_abcd.pdf']);
        Str::createRandomStringsUsingSequence(['ABCD', 'WXYZ'], fn () => throw new RuntimeException('Unexpected extra filename attempt'));

        $label = app(GenerateFileLabel::class)->handle($user, FileTypes::RECEIPT, new File(['extension' => 'pdf']));

        $this->assertSame('2026-11-08_10-42_Jan_Novak_kupni_smlouva_wxyz.pdf', $label);
    }

    public function test_three_collisions_return_the_available_fourth_filename(): void
    {
        $user = User::factory()->make(['name' => 'Jan Novák']);
        File::factory()->create(['label' => '2026-11-08_10-42_Jan_Novak_kupni_smlouva_abcd.pdf']);
        Str::createRandomStringsUsingSequence(['ABCD', 'ABCD', 'ABCD', 'WXYZ'], fn () => throw new RuntimeException('Unexpected extra filename attempt'));

        $label = app(GenerateFileLabel::class)->handle($user, FileTypes::RECEIPT, new File(['extension' => 'pdf']));

        $this->assertSame('2026-11-08_10-42_Jan_Novak_kupni_smlouva_wxyz.pdf', $label);
    }
}
