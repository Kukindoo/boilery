<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class IssueModelActivityTest extends FeatureTestCase
{
    public function test_issue_model_events_log_the_actor_and_changed_attributes(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $issue = Issue::factory()->create(['status' => IssueStatus::OPEN]);

        $created = Activity::forSubject($issue)->where('event', 'created')->sole();
        $this->assertSame('issues', $created->log_name);
        $this->assertTrue($created->causer->is($actor));
        $this->assertSame($issue->user_id, $created->attribute_changes->get('attributes')['user_id']);

        $issue->update(['status' => IssueStatus::CLOSED]);
        $updated = Activity::forSubject($issue)->where('event', 'updated')->sole();
        $this->assertSame('open', $updated->attribute_changes->get('old')['status']);
        $this->assertSame('closed', $updated->attribute_changes->get('attributes')['status']);
        $this->assertArrayNotHasKey('user_id', $updated->attribute_changes->get('attributes'));

        $issue->delete();
        $issue->restore();
        $this->assertSame(1, Activity::forSubject($issue)->where('event', 'deleted')->count());
        $this->assertSame(1, Activity::forSubject($issue)->where('event', 'restored')->count());
    }
}
