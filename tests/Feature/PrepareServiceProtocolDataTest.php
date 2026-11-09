<?php

namespace Tests\Feature;

use App\Actions\Works\PrepareServiceProtocolData;
use App\Models\SparePart;
use App\Models\Work;

class PrepareServiceProtocolDataTest extends FeatureTestCase
{
    public function test_protocol_uses_work_part_amounts_line_totals_and_grand_total(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $first = SparePart::factory()->create(['name' => 'Heating element', 'part_number' => 'ND-01', 'price_in_cents' => 1745]);
        $second = SparePart::factory()->create(['name' => 'Thermostat', 'part_number' => null, 'price_in_cents' => 1500]);
        $work->spareParts()->attach($first, ['amount' => 3]);
        $work->spareParts()->attach($second, ['amount' => 2]);
        $otherWork->spareParts()->attach($first, ['amount' => 20]);

        $data = app(PrepareServiceProtocolData::class)->handle($work);

        $this->assertCount(2, $data->parts);
        $this->assertSame('ND-01', $data->parts[0]->number);
        $this->assertSame('Heating element', $data->parts[0]->name);
        $this->assertSame(3, $data->parts[0]->quantity);
        $this->assertSame('CZK 52,35', $data->parts[0]->price);
        $this->assertNull($data->parts[1]->number);
        $this->assertSame('Thermostat', $data->parts[1]->name);
        $this->assertSame(2, $data->parts[1]->quantity);
        $this->assertSame('CZK 30,00', $data->parts[1]->price);
        $this->assertSame('CZK 82,35', $data->partsTotal);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();
        $this->assertStringContainsString('ND-01', $html);
        $this->assertStringContainsString('Heating element', $html);
        $this->assertStringContainsString('CZK 52,35', $html);
        $this->assertStringContainsString('CZK 82,35', $html);
    }

    public function test_protocol_renders_all_parts_beyond_the_five_blank_rows(): void
    {
        $work = Work::factory()->create();
        foreach (range(1, 6) as $number) {
            $part = SparePart::factory()->create(['name' => 'Part ' . $number, 'price_in_cents' => 100]);
            $work->spareParts()->attach($part, ['amount' => 2]);
        }

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();

        $this->assertCount(6, $data->parts);
        $this->assertStringContainsString('Part 6', $html);
        $this->assertStringContainsString('CZK 12,00', $html);
    }

    public function test_empty_work_has_zero_parts_total_and_blank_part_rows(): void
    {
        $data = app(PrepareServiceProtocolData::class)->handle(Work::factory()->create());

        $this->assertSame([], $data->parts);
        $this->assertSame('CZK 0,00', $data->partsTotal);
        $this->assertStringContainsString('ND celkem', view('pdfs.service-protocol-pdf', $data->toArray())->render());
    }

    public function test_preparation_reads_current_pivot_amounts_and_escapes_part_names(): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create(['name' => '<script>unsafe</script>', 'price_in_cents' => 1745]);
        $work->spareParts()->attach($part, ['amount' => 1]);
        $work->load('spareParts');
        $work->spareParts()->updateExistingPivot($part->id, ['amount' => 3]);

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();

        $this->assertSame(3, $data->parts[0]->quantity);
        $this->assertSame('CZK 52,35', $data->partsTotal);
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>unsafe</script>', $html);
    }
}
