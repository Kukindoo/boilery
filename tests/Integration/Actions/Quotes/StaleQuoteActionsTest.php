<?php

namespace Tests\Integration\Actions\Quotes;

use App\Actions\Quotes\DeleteQuote;
use App\Actions\Quotes\ForceDeleteQuote;
use App\Actions\Quotes\QuoteReassignment;
use App\Actions\Quotes\RestoreQuote;
use App\Actions\Quotes\UpdateQuote;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\QuoteStatus;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\Integration\IntegrationTestCase;

class StaleQuoteActionsTest extends IntegrationTestCase
{
    /** @return array<string, array{class-string, bool, array<string, mixed>}> */
    public static function invalidatedStates(): array
    {
        return [
            'accepted before update' => [UpdateQuote::class, false, ['status' => QuoteStatus::ACCEPTED]],
            'rejected before update' => [UpdateQuote::class, false, ['status' => QuoteStatus::REJECTED]],
            'accepted before reassignment' => [QuoteReassignment::class, false, ['status' => QuoteStatus::ACCEPTED]],
            'accepted before deletion' => [DeleteQuote::class, false, ['status' => QuoteStatus::ACCEPTED]],
            'already deleted' => [DeleteQuote::class, false, ['deleted_at' => '2026-11-06 12:00:00']],
            'already restored' => [RestoreQuote::class, true, ['deleted_at' => null]],
            'restored before permanent deletion' => [ForceDeleteQuote::class, true, ['deleted_at' => null]],
            'closed before permanent deletion' => [ForceDeleteQuote::class, true, ['status' => QuoteStatus::ACCEPTED]],
        ];
    }

    public static function ownershipActions(): array
    {
        return ['update' => [UpdateQuote::class, false], 'restore' => [RestoreQuote::class, true]];
    }

    #[DataProvider('invalidatedStates')]
    public function test_authorizes_current_state_and_preserves_changes_from_another_request(string $action, bool $trashed, array $changes): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $stale = Quote::factory()->create(['deleted_at' => $trashed ? now() : null]);
        Quote::withTrashed()->whereKey($stale->id)->update($changes);
        $before = $stale->fresh()->getRawOriginal();
        $activityCount = Activity::forSubject($stale)->count();

        try {
            $this->invokeAction($action, $stale);
            $this->fail('The changed quote state must invalidate the operation.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $stale->fresh()->getRawOriginal());
            $this->assertSame($activityCount + 1, Activity::forSubject($stale)->count());
            $attempt = Activity::forSubject($stale)->where('event', 'action_attempted')->sole();
            $this->assertSame($action, $attempt->getProperty('action'));
        }
    }

    #[DataProvider('ownershipActions')]
    public function test_former_owner_cannot_mutate_a_reassigned_quote(string $action, bool $trashed): void
    {
        $owner = User::factory()->customer()->create();
        $this->actingAs($owner);
        $stale = Quote::factory()->forCustomer($owner)->create(['deleted_at' => $trashed ? now() : null]);
        $newOwner = User::factory()->create();
        $stale->issue->update(['user_id' => $newOwner->id]);
        $before = $stale->fresh()->getRawOriginal();

        try {
            $this->invokeAction($action, $stale);
            $this->fail('The former owner must lose permission.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $stale->fresh()->getRawOriginal());
        }
    }

    public function test_update_does_not_recreate_a_permanently_deleted_quote(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $stale = Quote::factory()->create();
        Quote::whereKey($stale->id)->forceDelete();

        $this->expectException(ModelNotFoundException::class);

        $this->invokeAction(UpdateQuote::class, $stale);
    }

    public function test_reassignment_preserves_returned_instance_and_updates_current_record(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->create();
        $customer = User::factory()->create();
        $data = $this->data();
        $targetIssue = Issue::factory()->for($customer)->create();
        $data->issueId = $targetIssue->id;

        $result = app(QuoteReassignment::class)->handle($quote, $data);

        $this->assertSame($quote, $result);
        $this->assertSame($targetIssue->id, $quote->fresh()->issue_id);
    }

    private function invokeAction(string $action, Quote $quote): void
    {
        if (in_array($action, [UpdateQuote::class, QuoteReassignment::class], true)) {
            app($action)->handle($quote, $this->data());
        } else {
            app($action)->handle($quote);
        }
    }

    private function data(): QuoteDto
    {
        return new QuoteDto(
            firstName: 'Jan', lastName: 'Novák', email: 'jan@example.test',
            phone: '+420123456789', message: 'Updated problem', status: QuoteStatus::CONTACTED,
        );
    }
}
