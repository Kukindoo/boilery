<?php

namespace Tests\Feature;

use App\Actions\Works\PrepareServiceProtocolData;
use App\Models\AssemblyAction;
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

    public function test_protocol_renders_all_five_parts_at_the_limit(): void
    {
        $work = Work::factory()->create();
        foreach (range(1, 5) as $number) {
            $part = SparePart::factory()->create(['name' => 'Part ' . $number, 'price_in_cents' => 100]);
            $work->spareParts()->attach($part, ['amount' => 2]);
        }

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();

        $this->assertCount(5, $data->parts);
        foreach (range(1, 5) as $number) {
            $this->assertStringContainsString('Part ' . $number, $html);
        }
        $this->assertStringContainsString('CZK 10,00', $html);
    }

    public function test_empty_work_has_zero_parts_total_and_blank_part_rows(): void
    {
        $data = app(PrepareServiceProtocolData::class)->handle(Work::factory()->create());

        $this->assertSame([], $data->parts);
        $this->assertSame([], $data->assemblyActions);
        $this->assertSame('CZK 0,00', $data->assemblyActionsTotal);
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

    public function test_protocol_lists_assembly_actions_with_work_specific_prices_in_labour_section(): void
    {
        $work = Work::factory()->create(['work_performed_description' => 'Existing repair description']);
        $otherWork = Work::factory()->create();
        $first = AssemblyAction::factory()->create(['name' => 'Montáž ohřívače', 'code' => 'M-001']);
        $second = AssemblyAction::factory()->create(['name' => 'Kontrola zapojení', 'code' => 'K-002']);
        $unrelated = AssemblyAction::factory()->create(['name' => 'Unrelated action']);
        $work->assemblyActions()->attach($first, ['price_in_cents' => 1745]);
        $work->assemblyActions()->attach($second, ['price_in_cents' => 2500]);
        $otherWork->assemblyActions()->attach($first, ['price_in_cents' => 99999]);
        $otherWork->assemblyActions()->attach($unrelated, ['price_in_cents' => 1000]);

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $this->assertCount(2, $data->assemblyActions);
        $this->assertSame('M-001', $data->assemblyActions[0]->code);
        $this->assertSame('Montáž ohřívače', $data->assemblyActions[0]->name);
        $this->assertSame('CZK 17,45', $data->assemblyActions[0]->price);
        $this->assertSame('Kontrola zapojení', $data->assemblyActions[1]->name);
        $this->assertSame('CZK 25,00', $data->assemblyActions[1]->price);
        $this->assertSame('CZK 42,45', $data->assemblyActionsTotal);

        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();
        preg_match('/<section class="grid labour-section section".*?<\/section>/s', $html, $matches);
        $this->assertStringContainsString('Montáž ohřívače', $matches[0]);
        $this->assertStringContainsString('M-001', $matches[0]);
        $this->assertStringContainsString('K-002', $matches[0]);
        $this->assertStringContainsString('Kontrola zapojení', $matches[0]);
        $this->assertStringContainsString('CZK 17,45', $matches[0]);
        $this->assertStringContainsString('CZK 42,45', $matches[0]);
        $this->assertStringNotContainsString('Unrelated action', $html);
        $this->assertStringNotContainsString('CZK 999,99', $html);
        $this->assertStringContainsString('Existing repair description', $html);
    }

    public function test_protocol_renders_all_four_assembly_actions_at_the_limit(): void
    {
        $work = Work::factory()->create();
        foreach (range(1, 4) as $number) {
            $action = AssemblyAction::factory()->create(['name' => 'Assembly action ' . $number]);
            $work->assemblyActions()->attach($action, ['price_in_cents' => 1000]);
        }

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();

        $this->assertCount(4, $data->assemblyActions);
        foreach (range(1, 4) as $number) {
            $this->assertStringContainsString('Assembly action ' . $number, $html);
        }
        $this->assertStringContainsString('CZK 40,00', $html);
    }

    public function test_protocol_reads_current_assembly_action_prices_and_escapes_names(): void
    {
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create(['name' => '<script>unsafe action</script>']);
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $work->load('assemblyActions');
        $work->assemblyActions()->updateExistingPivot($action->id, ['price_in_cents' => 2350]);

        $data = app(PrepareServiceProtocolData::class)->handle($work);
        $html = view('pdfs.service-protocol-pdf', $data->toArray())->render();

        $this->assertSame('CZK 23,50', $data->assemblyActions[0]->price);
        $this->assertSame('CZK 23,50', $data->assemblyActionsTotal);
        $this->assertStringContainsString('&lt;script&gt;unsafe action&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>unsafe action</script>', $html);
    }
}
