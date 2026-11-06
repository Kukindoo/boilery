<?php

namespace Tests\Feature\Works;

use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Jobs\Works\GenerateServiceProtocolPdf;
use App\Livewire\Works\WorkPdfGeneration;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\FeatureTestCase;

class WorkPdfGenerationTest extends FeatureTestCase
{
    public function test_button_queues_generation_and_shows_queued_status_without_rendering_a_pdf(): void
    {
        Queue::fake([GenerateServiceProtocolPdf::class]);
        Http::preventStrayRequests();
        $user = User::factory()->admin()->create();
        $work = Work::factory()->create();

        $component = Livewire::actingAs($user)->test(WorkPdfGeneration::class, ['work' => $work])
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');

        $generationId = $work->fresh()->pdf_generation_id;
        $this->assertSame(PdfGenerationStatus::QUEUED, $work->fresh()->pdf_generation_status);
        $this->assertNotNull($generationId);
        $component->call('generate');
        $this->assertSame($generationId, $work->fresh()->pdf_generation_id);
        Queue::assertPushed(GenerateServiceProtocolPdf::class, fn (GenerateServiceProtocolPdf $job): bool => $job->workId === $work->id
            && $job->generationId === $generationId && $job->queue === 'default' && $job->afterCommit === true);
        Queue::assertPushed(GenerateServiceProtocolPdf::class, 1);
        Http::assertNothingSent();
        $this->assertDatabaseMissing('files', ['work_id' => $work->id]);
    }

    public function test_customer_cannot_queue_a_pdf_for_their_work(): void
    {
        Queue::fake([GenerateServiceProtocolPdf::class]);
        $user = User::factory()->customer()->create();
        $user->givePermissionTo(\App\Enums\Permissions::WORK_VIEW_OWN->label());
        $work = Work::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorkPdfGeneration::class, ['work' => $work])
            ->assertDontSee('Generovat PDF')
            ->call('generate')->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertNull($work->fresh()->pdf_generation_status);
    }

    public function test_user_cannot_monitor_another_customers_work(): void
    {
        $work = Work::factory()->for(User::factory())->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(WorkPdfGeneration::class, ['work' => $work])->assertForbidden();
    }

    public function test_reloading_the_component_preserves_the_processing_indicator(): void
    {
        $work = Work::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkPdfGeneration::class, ['work' => $work])
            ->assertSee('PDF se generuje.')
            ->assertSee('wire:poll.2s', false)
            ->assertDontSee('Generovat PDF');
    }

    public function test_completion_refreshes_the_page_to_display_the_new_attachment_and_success_message(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->create(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);
        $component = Livewire::actingAs($user)->test(WorkPdfGeneration::class, ['work' => $work]);
        $this->addSummary($work);
        $work->forceFill(['pdf_generation_status' => PdfGenerationStatus::COMPLETED])->save();

        $component->call('checkProgress')->assertRedirect(route('works.show', $work));

        $this->get(route('works.show', $work))
            ->assertSee('PDF je připraveno ke stažení v přílohách.')
            ->assertSee('summary.pdf')
            ->assertDontSee('wire:poll.2s', false);
    }

    public function test_failure_stops_polling_and_allows_retry_with_a_new_generation_id(): void
    {
        Queue::fake([GenerateServiceProtocolPdf::class]);
        $work = Work::factory()->create([
            'pdf_generation_status' => PdfGenerationStatus::PROCESSING,
            'pdf_generation_id' => '00000000-0000-4000-8000-000000000001',
        ]);
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkPdfGeneration::class, ['work' => $work]);
        $work->forceFill(['pdf_generation_status' => PdfGenerationStatus::FAILED])->save();

        $component->call('checkProgress')
            ->assertSee('PDF se nepodařilo vygenerovat.')
            ->assertDontSee('wire:poll.2s', false)
            ->assertSee('Generovat PDF')
            ->call('generate')
            ->assertSee('PDF čeká na zpracování.');

        $this->assertNotSame('00000000-0000-4000-8000-000000000001', $work->fresh()->pdf_generation_id);
        Queue::assertPushed(GenerateServiceProtocolPdf::class, 1);
    }

    public function test_an_existing_summary_prevents_another_generation(): void
    {
        Queue::fake([GenerateServiceProtocolPdf::class]);
        $work = Work::factory()->create();
        $this->addSummary($work);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkPdfGeneration::class, ['work' => $work])
            ->assertDontSee('Generovat PDF')->call('generate')->assertForbidden();

        Queue::assertNothingPushed();
    }

    private function addSummary(Work $work): void
    {
        $work->files()->create([
            'original_name' => 'summary.pdf', 'label' => 'summary.pdf',
            'file_type' => FileTypes::SERVICE_PROTOCOL, 'extension' => 'pdf',
            'size' => 100, 'mime_type' => 'application/pdf', 'path' => 'summary.pdf',
        ]);
    }
}
