<?php

namespace Tests\Feature\Quotes;

use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Enums\Permissions;
use App\Jobs\Pdf\GeneratePdf;
use App\Livewire\Files\FilePreviewCard;
use App\Livewire\Pdf\PdfGeneration;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class QuotePdfGenerationTest extends FeatureTestCase
{
    public function test_show_previews_the_summary_before_the_generate_button_without_queueing_a_job(): void
    {
        Queue::fake([GeneratePdf::class]);
        $quote = Quote::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSeeInOrder(['<iframe', '</iframe>'], false)
            ->assertSee('Generovat PDF')
            ->assertSee('sandbox=""', false)
            ->assertSee('Bez příloh.')
            ->assertSeeLivewire(PdfGeneration::class);

        Queue::assertNothingPushed();
        $this->assertTrue($quote->files()->doesntExist());
    }

    public function test_preview_includes_attachment_images_but_excludes_generated_summaries(): void
    {
        $quote = Quote::factory()->create();
        $file = File::factory()->create(['mime_type' => 'image/jpeg']);
        $quote->files()->attach($file);
        $this->addSummary($quote);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertViewHas('pdfContent', fn (string $content): bool => mb_substr_count($content, '<h3>Příloha:') === 1
                && str_contains($content, route('files.preview', ['quote', $quote, $file])));
    }

    public function test_preview_does_not_include_files_the_viewer_cannot_access(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_ALL);
        $quote = Quote::factory()->create();
        $quote->files()->attach(File::factory()->create());

        $this->actingAs($user)->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertViewHas('pdfContent', fn (string $content): bool => str_contains($content, 'Bez příloh.')
                && ! str_contains($content, '<h3>Příloha:'))
            ->assertDontSee('Generovat PDF');
    }

    public function test_button_queues_generation_and_shows_queued_status_without_rendering_a_pdf(): void
    {
        Queue::fake([GeneratePdf::class]);
        Http::preventStrayRequests();
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create();

        $component = Livewire::actingAs($user)->test(PdfGeneration::class, ['model' => $quote])
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');

        $generationId = $quote->fresh()->pdf_generation_id;
        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
        $this->assertNotNull($generationId);
        $component->call('generate');
        $this->assertSame($generationId, $quote->fresh()->pdf_generation_id);
        Queue::assertPushed(GeneratePdf::class, fn (GeneratePdf $job): bool => $job->modelType === 'quote' && $job->modelId === $quote->id
            && $job->generationId === $generationId && $job->queue === 'default' && $job->afterCommit === true);
        Queue::assertPushed(GeneratePdf::class, 1);
        Http::assertNothingSent();
        $this->assertTrue($quote->files()->doesntExist());
    }

    public function test_post_endpoint_queues_the_job_and_get_cannot_generate_a_pdf(): void
    {
        Queue::fake([GeneratePdf::class]);
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create();

        $this->actingAs($user)->get(route('quotes.generate-pdf', $quote))->assertMethodNotAllowed();
        $this->post(route('quotes.generate-pdf', $quote))->assertRedirectToRoute('quotes.show', $quote);

        Queue::assertPushed(GeneratePdf::class, 1);
        $this->assertSame(PdfGenerationStatus::QUEUED, $quote->fresh()->pdf_generation_status);
    }

    public function test_customer_cannot_queue_a_pdf_for_their_quote(): void
    {
        Queue::fake([GeneratePdf::class]);
        $user = User::factory()->customer()->create();
        $quote = Quote::factory()->forCustomer($user)->create();

        Livewire::actingAs($user)->test(PdfGeneration::class, ['model' => $quote])
            ->assertDontSee('Generovat PDF')
            ->call('generate')->assertForbidden();
        $this->post(route('quotes.generate-pdf', $quote))->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertNull($quote->fresh()->pdf_generation_status);
    }

    public function test_user_cannot_monitor_another_customers_quote(): void
    {
        $quote = Quote::factory()->forCustomer(User::factory())->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(PdfGeneration::class, ['model' => $quote])->assertForbidden();
    }

    public function test_reloading_the_component_preserves_the_processing_indicator(): void
    {
        $quote = Quote::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(PdfGeneration::class, ['model' => $quote])
            ->assertSee('PDF se generuje.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');
    }

    public function test_completion_refreshes_the_page_to_display_the_new_attachment_and_success_message(): void
    {
        $user = User::factory()->admin()->create();
        $quote = Quote::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);
        $component = Livewire::actingAs($user)->test(PdfGeneration::class, ['model' => $quote]);
        $summary = $this->addSummary($quote);
        $quote->forceFill(['pdf_generation_status' => PdfGenerationStatus::COMPLETED])->save();

        $component->call('checkProgress')
            ->assertDispatched('toast-show', slots: ['text' => 'PDF bylo úspěšně vygenerováno. Najdete ho v přílohách.'], dataset: ['variant' => 'success'])
            ->assertRedirect(route('quotes.show', $quote));

        $this->get(route('quotes.show', $quote))
            ->assertSee('PDF je připraveno ke stažení v přílohách.')
            ->assertSeeLivewire(FilePreviewCard::class)
            ->assertSee(route('files.preview', ['quote', $quote, $summary]), false)
            ->assertDontSee('wire:poll.2s', false);
    }

    public function test_failure_stops_polling_and_allows_retry_with_a_new_generation_id(): void
    {
        Queue::fake([GeneratePdf::class]);
        $quote = Quote::factory()->create([
            'pdf_generation_status' => PdfGenerationStatus::PROCESSING,
            'pdf_generation_id' => '00000000-0000-4000-8000-000000000001',
        ]);
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(PdfGeneration::class, ['model' => $quote]);
        $quote->forceFill(['pdf_generation_status' => PdfGenerationStatus::FAILED])->save();

        $component->call('checkProgress')
            ->assertSee('PDF se nepodařilo vygenerovat.')
            ->assertDontSee('wire:poll.2s', false)
            ->assertSee('Generovat PDF')
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.');

        $this->assertNotSame('00000000-0000-4000-8000-000000000001', $quote->fresh()->pdf_generation_id);
        Queue::assertPushed(GeneratePdf::class, 1);
    }

    public function test_an_existing_summary_prevents_another_generation(): void
    {
        Queue::fake([GeneratePdf::class]);
        $quote = Quote::factory()->create();
        $this->addSummary($quote);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(PdfGeneration::class, ['model' => $quote])
            ->assertDontSee('Generovat PDF')->call('generate')->assertForbidden();

        Queue::assertNothingPushed();
    }

    private function addSummary(Quote $quote): File
    {
        return $quote->files()->create([
            'original_name' => 'summary.pdf', 'label' => 'summary.pdf',
            'file_type' => FileTypes::QUOTE_SUMMARY, 'extension' => 'pdf',
            'size' => 100, 'mime_type' => 'application/pdf', 'path' => 'summary.pdf',
        ]);
    }
}
