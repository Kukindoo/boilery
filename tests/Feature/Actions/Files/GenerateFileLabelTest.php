<?php

namespace Tests\Feature\Actions\Files;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\Quote;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class GenerateFileLabelTest extends FeatureTestCase
{
    /** @return array<string, array{?string, list<string>, string}> */
    public static function existingLabels(): array
    {
        return [
            'unused label' => ['pdf', [], '.pdf'],
            'first duplicate' => ['pdf', ['.pdf'], '_1.pdf'],
            'several duplicates' => ['pdf', ['.pdf', '_1.pdf', '_2.pdf'], '_3.pdf'],
            'available gap' => ['pdf', ['.pdf', '_2.pdf'], '_1.pdf'],
            'different extension' => ['pdf', ['.jpg'], '.pdf'],
            'unused extensionless label' => [null, [], ''],
            'extensionless duplicates' => [null, ['', '_1'], '_2'],
        ];
    }

    /** @param list<string> $existingSuffixes */
    #[DataProvider('existingLabels')]
    public function test_returns_the_first_available_label(?string $extension, array $existingSuffixes, string $expectedSuffix): void
    {
        $quote = Quote::factory()->create(['first_name' => 'Jiří', 'last_name' => 'Novák']);
        $baseLabel = "Jiri_Novak_{$quote->id}_shrnuti_poptavky";
        $quote->load('files');

        foreach ($existingSuffixes as $suffix) {
            $this->createFile($quote, $baseLabel . $suffix);
        }

        $label = app(GenerateFileLabel::class)->handle($quote, FileTypes::QUOTE_SUMMARY, $extension);

        $this->assertSame($baseLabel . $expectedSuffix, $label);
    }

    public function test_files_belonging_to_another_quote_do_not_reserve_the_label(): void
    {
        $quote = Quote::factory()->create(['first_name' => 'Jan', 'last_name' => 'Novak']);
        $otherQuote = Quote::factory()->create();
        $expectedLabel = "Jan_Novak_{$quote->id}_shrnuti_poptavky.pdf";
        $this->createFile($otherQuote, $expectedLabel);

        $label = app(GenerateFileLabel::class)->handle($quote, FileTypes::QUOTE_SUMMARY, 'pdf');

        $this->assertSame($expectedLabel, $label);
    }

    private function createFile(Quote $quote, string $label): void
    {
        $quote->files()->create([
            'label' => $label,
            'original_name' => $label,
            'path' => 'files/' . $label,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'file_type' => FileTypes::QUOTE_SUMMARY,
        ]);
    }
}
