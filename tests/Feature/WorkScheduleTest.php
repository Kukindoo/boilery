<?php

namespace Tests\Feature;

use App\Livewire\Works\WorkEdit;
use App\Livewire\Works\WorkSchedule;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;

class WorkScheduleTest extends FeatureTestCase
{
    public function test_schedule_can_be_set_in_prague_time_and_stored_in_utc(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['work_accepted_at' => null]);

        Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work])
            ->assertSee('Schedule')
            ->call('open')
            ->set('form.scheduleDate', '2026-07-15')
            ->set('form.scheduleTime', '10:30')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-07-15 08:30:00', Work::query()->sole()->schedule_at->toDateTimeString());
    }

    public function test_schedule_can_be_loaded_edited_and_cleared(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => '2026-11-20 09:30:00', 'work_accepted_at' => null]);

        Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work])
            ->assertSet('form.scheduleDate', '2026-11-20')
            ->assertSet('form.scheduleTime', '10:30')
            ->set('form.scheduleTime', '14:15')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-11-20 13:15:00', $work->refresh()->schedule_at->toDateTimeString());

        Livewire::test(WorkSchedule::class, ['work' => $work])
            ->set('form.scheduleDate', null)->set('form.scheduleTime', null)
            ->call('submit')->assertHasNoErrors();

        $this->assertNull($work->refresh()->schedule_at);
        $this->get(route('works.show', $work))->assertDontSee('https://calendar.google.com/calendar/r/eventedit');
        $this->assertNull($work->googleCalendarUrl());
    }

    public function test_incomplete_or_invalid_schedule_is_rejected_without_saving(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => null, 'work_accepted_at' => null]);
        $component = Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work]);

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

    public function test_schedule_must_be_on_or_after_the_acceptance_date(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create([
            'work_accepted_at' => '2026-11-20',
            'work_started_at' => null,
            'work_completed_at' => null,
            'schedule_at' => null,
        ]);
        $component = Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work]);

        $component->set('form.scheduleDate', '2026-11-19')
            ->set('form.scheduleTime', '10:00')
            ->call('submit')->assertHasErrors(['form.scheduleDate' => 'after_or_equal']);

        $this->assertNull($work->refresh()->schedule_at);

        $component->set('form.scheduleDate', '2026-11-20')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-11-20 09:00:00', $work->refresh()->schedule_at->toDateTimeString());

        Livewire::test(WorkSchedule::class, ['work' => $work])
            ->set('form.scheduleDate', '2026-11-21')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-11-21 09:00:00', $work->refresh()->schedule_at->toDateTimeString());
    }

    public function test_time_is_limited_to_nine_through_nineteen_inclusively(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => null, 'work_accepted_at' => null]);
        $component = Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work])
            ->set('form.scheduleDate', '2026-11-20');

        $component->set('form.scheduleTime', '08:59')->call('submit')
            ->assertHasErrors(['form.scheduleTime' => 'after_or_equal']);
        $component->set('form.scheduleTime', '19:01')->call('submit')
            ->assertHasErrors(['form.scheduleTime' => 'before_or_equal']);
        $this->assertNull($work->refresh()->schedule_at);

        $component->set('form.scheduleTime', '09:00')->call('submit')->assertHasNoErrors();
        $this->assertSame('2026-11-20 08:00:00', $work->refresh()->schedule_at->toDateTimeString());

        $component->set('form.scheduleTime', '19:00')->call('submit')->assertHasNoErrors();
        $this->assertSame('2026-11-20 18:00:00', $work->refresh()->schedule_at->toDateTimeString());
    }

    public function test_saving_work_details_preserves_its_schedule(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => '2026-11-20 09:30:00', 'work_accepted_at' => null]);

        Livewire::actingAs($user)->test(WorkEdit::class, ['work' => $work])
            ->set('form.notes', 'Updated notes')->call('submit')->assertHasNoErrors();

        $this->assertSame('2026-11-20 09:30:00', $work->refresh()->schedule_at->toDateTimeString());
        $this->assertSame('Updated notes', $work->notes);
    }

    public function test_view_only_user_cannot_change_the_schedule(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(\App\Enums\Permissions::WORK_VIEW_OWN);
        $work = Work::factory()->forCustomer($user)->create(['schedule_at' => null, 'work_accepted_at' => null]);

        Livewire::actingAs($user)->test(WorkSchedule::class, ['work' => $work])
            ->assertDontSee('wire:click="open"', false)
            ->call('open')->assertForbidden();
        Livewire::test(WorkSchedule::class, ['work' => $work])
            ->set('form.scheduleDate', '2026-11-20')->set('form.scheduleTime', '10:00')
            ->call('submit')->assertForbidden();

        $this->assertNull($work->refresh()->schedule_at);
    }

    public function test_calendar_link_prefills_encoded_details_without_mutating_schedule(): void
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
        $this->assertSame('20260715T083000Z/20260715T113000Z', $query['dates']);
        $this->assertSame('Zakázka SERVIS & 123', $query['text']);
        $this->assertSame("Oprava & kontrola\n" . route('works.show', $work), $query['details']);
        $this->assertSame('Praha, Dlouhá 1', $query['location']);
        $this->assertSame('Europe/Prague', $query['stz']);
        $this->assertSame('2026-07-15 08:30:00', $work->schedule_at->toDateTimeString());

        $this->actingAs($user)->get(route('works.show', $work))
            ->assertSee($work->googleCalendarUrl())
            ->assertSee('15-07-2026')
            ->assertSee('10:30')
            ->assertSee('Přidat do Google Kalendáře');
    }
}
