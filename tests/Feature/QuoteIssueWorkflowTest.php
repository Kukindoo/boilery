<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Livewire\Quotes\CustomerQuoteRequestPage;
use App\Models\File;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;

class QuoteIssueWorkflowTest extends FeatureTestCase
{
    public function test_submission_creates_customer_open_issue_and_quote_with_owner_through_issue(): void
    {
        Notification::fake();
        $userCount = User::count();
        Livewire::test(CustomerQuoteRequestPage::class)
            ->set('form.firstName', 'Jan')->set('form.lastName', 'Novák')
            ->set('form.email', 'new-case@example.test')->set('form.phone', '123456789')
            ->set('form.message', 'Needs repair')->call('submit')->assertHasNoErrors();

        $user = User::where('email', 'new-case@example.test')->sole();
        $issue = Issue::query()->sole();
        $quote = Quote::query()->sole();
        $this->assertDatabaseCount('users', $userCount + 1);
        $this->assertSame($user->id, $issue->user_id);
        $this->assertSame(IssueStatus::OPEN, $issue->status);
        $this->assertSame($issue->id, $quote->issue_id);
        $this->assertTrue($quote->user->is($user));
        $this->assertTrue($user->quotes()->sole()->is($quote));
        $this->assertArrayNotHasKey('user_id', $quote->getAttributes());
    }

    public function test_attachment_failure_rolls_back_all_three_models_and_keeps_form_inputs(): void
    {
        Notification::fake();
        $usersBefore = User::count();
        Storage::fake(config('filesystems.default'));
        $failure = new RuntimeException('Upload failed');
        File::creating(function () use ($failure): void {
            throw $failure;
        });
        $component = Livewire::test(CustomerQuoteRequestPage::class)
            ->set('form.firstName', 'Jan')->set('form.lastName', 'Novák')
            ->set('form.email', 'failed-case@example.test')->set('form.phone', '123456789')
            ->set('form.message', 'Needs repair')
            ->set('form.fileLabel', UploadedFile::fake()->image('label.jpg'));
        try {
            $component->call('submit');
            $this->fail('Submission must fail when attachment storage fails.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertDatabaseCount('users', $usersBefore);
        $this->assertDatabaseCount('issues', 0);
        $this->assertDatabaseCount('quotes', 0);
        $component->assertSet('form.email', 'failed-case@example.test')->assertSet('submitted', false);
        $this->assertDatabaseCount('files', 0);
        $this->assertSame([], Storage::disk()->allFiles());
        Notification::assertNothingSent();
    }

    public function test_signed_in_customer_reuses_account_but_gets_a_new_issue_for_each_submission(): void
    {
        Notification::fake();
        $customer = User::factory()->customer()->create(['name' => 'Jan Novák']);
        $usersBefore = User::count();
        for ($submission = 0; $submission < 2; $submission++) {
            Livewire::actingAs($customer)->test(CustomerQuoteRequestPage::class)
                ->set('form.message', 'Needs repair')->call('submit')->assertHasNoErrors();
        }
        $this->assertDatabaseCount('users', $usersBefore);
        $this->assertSame(2, $customer->issues()->count());
        $this->assertSame(2, $customer->quotes()->count());
    }
}
