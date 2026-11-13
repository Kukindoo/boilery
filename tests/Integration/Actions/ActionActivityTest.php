<?php

namespace Tests\Integration\Actions;

use App\Actions\Files\DeleteFile;
use App\Actions\Files\UploadFile;
use App\Actions\Files\UploadQuoteFile;
use App\Actions\Quotes\UpdateQuote;
use App\Actions\Users\CreateCustomer;
use App\Dtos\NamePlate;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\FileTypes;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Integration\IntegrationTestCase;

class ActionActivityTest extends IntegrationTestCase
{
    public static function callerOutcomes(): array
    {
        return ['outer transaction rolls back' => [true], 'caller catches failure and commits' => [false]];
    }

    public function test_successful_actions_log_model_subject_actor_and_action_name(): void
    {
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $customer = app(CreateCustomer::class)->handle(new NamePlate('Jan', 'Novák', 'jan@example.test', '123456789'));
        $quote = Quote::factory()->forCustomer($customer)->create();

        app(UpdateQuote::class)->handle($quote, $this->data());

        $customerActivity = Activity::inLog('users')->where('event', 'action_completed')->sole();
        $this->assertTrue($customerActivity->subject->is($customer));
        $this->assertTrue($customerActivity->causer->is($actor));
        $this->assertSame(CreateCustomer::class, $customerActivity->getProperty('action'));
        $quoteActivity = Activity::inLog('quotes')->where('event', 'action_completed')->sole();
        $this->assertTrue($quoteActivity->subject->is($quote));
        $this->assertTrue($quoteActivity->causer->is($actor));
        $this->assertSame(UpdateQuote::class, $quoteActivity->getProperty('action'));
    }

    public function test_upload_logs_to_issue_and_deletion_logs_to_deleted_file(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $file = app(UploadQuoteFile::class)->handle(UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'), $quote, FileTypes::RECEIPT);

        app(DeleteFile::class)->handle($file);

        $entries = Activity::inLog('files')->where('event', 'action_completed')->orderBy('id')->get();
        $uploadEntry = Activity::forSubject($quote->issue)->where('description', 'File uploaded')->sole();
        $this->assertTrue($uploadEntry->causer->is($actor));
        $this->assertSame(UploadFile::class, $uploadEntry->getProperty('action'));
        $this->assertSame($file->id, $uploadEntry->getProperty('file_id'));
        $this->assertCount(1, $entries);
        $this->assertSame([DeleteFile::class], $entries->map(fn (Activity $activity) => $activity->getProperty('action'))->all());
        foreach ($entries as $entry) {
            $this->assertSame($file->id, $entry->subject_id);
            $this->assertSame($file->getMorphClass(), $entry->subject_type);
            $this->assertTrue($entry->causer->is($actor));
        }
    }

    #[DataProvider('callerOutcomes')]
    public function test_failed_attempt_survives_nested_caller_transactions_exactly_once(bool $rollback): void
    {
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $quote = Quote::factory()->forCustomer(User::factory())->create();
        $before = $quote->fresh()->getRawOriginal();
        $failure = new RuntimeException('Cannot store success activity.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            if ($activity->description === 'Quote updated') {
                throw $failure;
            }
        });

        try {
            DB::transaction(function () use ($quote, $rollback, $failure): void {
                DB::transaction(function () use ($quote, $rollback, $failure): void {
                    try {
                        app(UpdateQuote::class)->handle($quote, $this->data());
                        $this->fail('The original failure must propagate.');
                    } catch (RuntimeException $exception) {
                        $this->assertSame($failure, $exception);
                        if ($rollback) {
                            throw $exception;
                        }
                    }
                });
            });
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame($before, $quote->fresh()->getRawOriginal());
        $attempt = Activity::inLog('quotes')->where('event', 'action_attempted')->sole();
        $this->assertTrue($attempt->subject->is($quote));
        $this->assertTrue($attempt->causer->is($actor));
        $this->assertSame(UpdateQuote::class, $attempt->getProperty('action'));
        $this->assertSame(RuntimeException::class, $attempt->getProperty('exception'));
        $this->assertNotEmpty($attempt->getProperty('attempt_id'));
        $this->assertDatabaseMissing('activity_log', ['event' => 'action_completed', 'description' => 'Quote updated']);
    }

    public function test_logging_failure_does_not_replace_the_original_action_exception(): void
    {
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $quote = Quote::factory()->create();
        $failure = new RuntimeException('Original failure.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            throw $activity->event === 'action_attempted' ? new RuntimeException('Attempt logging failed.') : $failure;
        });

        try {
            app(UpdateQuote::class)->handle($quote, $this->data());
            $this->fail('The original failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    private function data(): QuoteDto
    {
        return new QuoteDto(firstName: 'Jan', lastName: 'Novák', email: 'jan@example.test', phone: '123456789', message: 'Updated problem');
    }
}
