<?php

namespace Tests\Integration\Quotes;

use App\Livewire\Quotes\CustomerQuoteRequestPage;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Queue as BaseQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CustomerQuoteSubmissionTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->assertExitCode(0);
        $this->beforeApplicationDestroyed(function (): void {
            BaseQueue::createPayloadUsing(null);
            DB::disconnect('notification_queue');
            $this->artisan('db:wipe')->assertExitCode(0);
            RefreshDatabaseState::$migrated = false;
        });

        config([
            'database.connections.notification_queue' => config('database.connections.' . config('database.default')),
            'queue.default' => 'database',
            'queue.connections.database.connection' => 'notification_queue',
            'queue.connections.database.after_commit' => false,
            'contacts.admin_email' => 'admin@example.test',
        ]);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_notifications_wait_for_outermost_commit_and_deliver_committed_quote(): void
    {
        $component = $this->submission();
        DB::beginTransaction();
        try {
            $component->submit();

            $this->assertDatabaseCount('quotes', 1);
            $this->assertSame(0, Queue::size());
            $this->assertFalse($component->submitted);
            $this->assertSame('jan@example.test', $component->form->email);
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        $this->assertTrue($component->submitted);
        $this->assertSame('', $component->form->email);
        $this->assertSame(2, Queue::size());
        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);
        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);
        $this->assertSame(0, Queue::size());
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);
        $this->assertSame('jan@example.test', $messages->first()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_outermost_rollback_discards_notifications_and_preserves_inputs(): void
    {
        $usersBefore = User::count();
        $component = $this->submission();
        DB::beginTransaction();
        try {
            $component->submit();
            $this->assertSame(0, Queue::size());
        } finally {
            DB::rollBack();
        }

        $this->assertSame(0, Queue::size());
        $this->assertFalse($component->submitted);
        $this->assertSame('jan@example.test', $component->form->email);
        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('users', $usersBefore);
    }

    public function test_queue_preparation_failure_after_creation_rolls_back_and_preserves_inputs(): void
    {
        $usersBefore = User::count();
        $component = $this->submission();
        $failure = new RuntimeException('Queue payload failed');
        BaseQueue::createPayloadUsing(function () use ($failure): array {
            $this->assertDatabaseCount('quotes', 1);
            $this->assertDatabaseCount('issues', 1);
            throw $failure;
        });

        try {
            $component->submit();
            $this->fail('Queue preparation must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertFalse($component->submitted);
        $this->assertSame('jan@example.test', $component->form->email);
        $this->assertSame('Needs repair', $component->form->message);
        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('users', $usersBefore);
        $this->assertSame(0, Queue::size());
    }

    public function test_deferred_enqueue_failure_preserves_inputs_but_cannot_undo_committed_quote(): void
    {
        $component = $this->submission();
        $failure = new RuntimeException('Queue unavailable');
        Event::listen(JobQueueing::class, function () use ($failure): void {
            $this->assertSame(0, DB::transactionLevel());
            throw $failure;
        });

        try {
            $component->submit();
            $this->fail('Enqueueing must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertFalse($component->submitted);
        $this->assertSame('jan@example.test', $component->form->email);
        $this->assertSame('Needs repair', $component->form->message);
        $this->assertDatabaseCount('quotes', 1);
        $quote = Quote::query()->sole();
        $this->assertSame('jan@example.test', $quote->issue->user->email);
        $this->assertSame(0, Queue::size());
    }

    private function submission(): CustomerQuoteRequestPage
    {
        return Livewire::test(CustomerQuoteRequestPage::class)
            ->set('form.firstName', 'Jan')->set('form.lastName', 'Novák')
            ->set('form.email', 'jan@example.test')->set('form.phone', '123456789')
            ->set('form.message', 'Needs repair')->instance();
    }
}
