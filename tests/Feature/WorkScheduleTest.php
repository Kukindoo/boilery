<?php

namespace Tests\Feature;

use App\Livewire\Works\WorkCreate;
use App\Livewire\Works\WorkEdit;
use App\Models\Issue;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;

class WorkScheduleTest extends FeatureTestCase
{
    public function test_schedule_can_be_created_in_prague_time_and_stored_in_utc(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->for($user)->create();

        Livewire::actingAs($user)->test(WorkCreate::class)
            ->set('form.issueId', $issue->id)
            ->set('form.orderId', 'SCHEDULE-001')
            ->set('form.scheduleDate', '2026-07-15')
            ->set('form.scheduleTime', '10:30')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-07-15 08:30:00', Work::query()->sole()->schedule_at->toDateTimeString());
    }

    public function test_schedule_can_be_loaded_edited_and_cleared(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => '2026-11-20 09:30:00']);

        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->assertSet('form.scheduleDate', '2026-11-20')
            ->assertSet('form.scheduleTime', '10:30')
            ->set('form.scheduleTime', '14:15')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-11-20 13:15:00', $work->refresh()->schedule_at->toDateTimeString());

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.scheduleDate', null)->set('form.scheduleTime', null)
            ->call('submit')->assertHasNoErrors();

        $this->assertNull($work->refresh()->schedule_at);
        $this->get(route('works.show', $work))->assertDontSee('https://calendar.google.com/calendar/r/eventedit');
        $this->assertNull($work->googleCalendarUrl());
    }

    public function test_incomplete_or_invalid_schedule_is_rejected_without_saving(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => null]);
        $component = Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work]);

        $component->set('form.scheduleDate', '2026-11-20')->call('submit')
            ->assertHasErrors(['form.scheduleTime' => 'required_with']);
        $component->set('form.scheduleTime', '25:00')->call('submit')
            ->assertHasErrors(['form.scheduleTime' => 'date_format']);
        $component->set('form.scheduleTime', '10:00')->set('form.scheduleDate', 'invalid')->call('submit')
            ->assertHasErrors(['form.scheduleDate' => 'date_format']);
        $component->set('form.scheduleDate', null)->call('submit')
            ->assertHasErrors(['form.scheduleDate' => 'required_with']);

        $this->assertNull($work->refresh()->schedule_at);
    }

    public function test_calendar_link_prefills_encoded_details_and_one_hour_without_mutating_schedule(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create([
            'schedule_at' => '2026-07-15 08:30:00',
            'order_id' => 'SERVIS & 123',
            'reported_problem' => 'Oprava & kontrola',
            'transport_to' => 'Praha, Dlouhá 1',
        ]);

        parse_str(parse_url($work->googleCalendarUrl(), PHP_URL_QUERY), $query);

        $this->assertSame('https://calendar.google.com/calendar/r/eventedit', strtok($work->googleCalendarUrl(), '?'));
        $this->assertSame('TEMPLATE', $query['action']);
        $this->assertSame('20260715T083000Z/20260715T093000Z', $query['dates']);
        $this->assertSame('Zakázka SERVIS & 123', $query['text']);
        $this->assertSame("Oprava & kontrola\n" . route('works.show', $work), $query['details']);
        $this->assertSame('Praha, Dlouhá 1', $query['location']);
        $this->assertSame('Europe/Prague', $query['stz']);
        $this->assertSame('2026-07-15 08:30:00', $work->schedule_at->toDateTimeString());

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->googleCalendarUrl())
            ->assertSee('15-07-2026 10:30')
            ->assertSee('Přidat do Google Kalendáře');
    }
}
