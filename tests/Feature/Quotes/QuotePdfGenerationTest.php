<?php

namespace Tests\Feature\Quotes;

use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Jobs\Quotes\GenerateQuoteSummaryPdf;
use App\Livewire\Quotes\QuotePdfGeneration;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class QuotePdfGenerationTest extends FeatureTestCase
{
    public function test_button_queues_generation_and_shows_queued_status_without_rendering_a_pdf(): void
    {
        Queue::fake([GenerateQuoteSummaryPdf::class]);
        Http::preventStrayRequests();
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create();

        $component = Livewire::actingAs($user)->test(QuotePdfGeneration::class, ['quote' => $quote])
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');

        $generationId = $quote->fresh()->pdf_generation_id;
        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
        $this->assertNotNull($generationId);
        $component->call('generate');
        $this->assertSame($generationId, $quote->fresh()->pdf_generation_id);
        Queue::assertPushed(GenerateQuoteSummaryPdf::class, fn (GenerateQuoteSummaryPdf $job): bool => $job->quoteId === $quote->id
            && $job->generationId === $generationId && $job->queue === 'default' && $job->afterCommit === true);
        Queue::assertPushed(GenerateQuoteSummaryPdf::class, 1);
        Http::assertNothingSent();
        $this->assertDatabaseMissing('files', ['quote_id' => $quote->id]);
    }

    public function test_post_endpoint_queues_the_job_and_get_cannot_generate_a_pdf(): void
    {
        Queue::fake([GenerateQuoteSummaryPdf::class]);
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create();

        $this->actingAs($user)->get(route('quotes.generate-pdf', $quote))->assertMethodNotAllowed();
        $this->post(route('quotes.generate-pdf', $quote))->assertRedirectToRoute('quotes.show', $quote);

        Queue::assertPushed(GenerateQuoteSummaryPdf::class, 1);
        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
    }

    public function test_customer_cannot_queue_a_pdf_for_their_quote(): void
    {
        Queue::fake([GenerateQuoteSummaryPdf::class]);
        $user = User::factory()->customer()->create();
        $quote = Quote::factory()->for($user)->create();

        Livewire::actingAs($user)->test(QuotePdfGeneration::class, ['quote' => $quote])
            ->assertDontSee('Generovat PDF')
            ->call('generate')->assertForbidden();
        $this->post(route('quotes.generate-pdf', $quote))->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertNull($quote->fresh()->pdf_generation_status);
    }

    public function test_user_cannot_monitor_another_customers_quote(): void
    {
        $quote = Quote::factory()->for(User::factory())->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(QuotePdfGeneration::class, ['quote' => $quote])->assertForbidden();
    }

    public function test_reloading_the_component_preserves_the_processing_indicator(): void
    {
        $quote = Quote::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(QuotePdfGeneration::class, ['quote' => $quote])
            ->assertSee('PDF se generuje.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');
    }

    public function test_completion_refreshes_the_page_to_display_the_new_attachment_and_success_message(): void
    {
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);
        $component = Livewire::actingAs($user)->test(QuotePdfGeneration::class, ['quote' => $quote]);
        $this->addSummary($quote);
        $quote->forceFill(['pdf_generation_status' => PdfGenerationStatus::COMPLETED])->save();

        $component->call('checkProgress')->assertRedirect(route('quotes.show', $quote));

        $this->get(route('quotes.show', $quote))
            ->assertSee('PDF je připraveno ke stažení v přílohách.')
            ->assertSee('summary.pdf')
            ->assertDontSee('wire:poll.2s', false);
    }

    public function test_failure_stops_polling_and_allows_retry_with_a_new_generation_id(): void
    {
        Queue::fake([GenerateQuoteSummaryPdf::class]);
        $quote = Quote::factory()->create([
            'pdf_generation_status' => PdfGenerationStatus::PROCESSING,
            'pdf_generation_id' => '00000000-0000-4000-8000-000000000001',
        ]);
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(QuotePdfGeneration::class, ['quote' => $quote]);
        $quote->forceFill(['pdf_generation_status' => PdfGenerationStatus::FAILED])->save();

        $component->call('checkProgress')
            ->assertSee('PDF se nepodařilo vygenerovat.')
            ->assertDontSee('wire:poll.2s', false)
            ->assertSee('Generovat PDF')
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.');

        $this->assertNotSame('00000000-0000-4000-8000-000000000001', $quote->fresh()->pdf_generation_id);
        Queue::assertPushed(GenerateQuoteSummaryPdf::class, 1);
    }

    public function test_an_existing_summary_prevents_another_generation(): void
    {
        Queue::fake([GenerateQuoteSummaryPdf::class]);
        $quote = Quote::factory()->create();
        $this->addSummary($quote);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(QuotePdfGeneration::class, ['quote' => $quote])
            ->assertDontSee('Generovat PDF')->call('generate')->assertForbidden();

        Queue::assertNothingPushed();
    }

    private function addSummary(Quote $quote): void
    {
        $quote->files()->create([
            'original_name' => 'summary.pdf', 'label' => 'summary.pdf',
            'file_type' => FileTypes::QUOTE_SUMMARY, 'extension' => 'pdf',
            'size' => 100, 'mime_type' => 'application/pdf', 'path' => 'summary.pdf',
        ]);
    }
}
