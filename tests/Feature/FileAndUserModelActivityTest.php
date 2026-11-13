<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class FileAndUserModelActivityTest extends FeatureTestCase
{
    public function test_file_creation_update_and_deletion_log_model_changes(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $file = File::factory()->create(['original_name' => 'before.pdf']);
        $created = Activity::forSubject($file)->where('event', 'created')->sole();
        $this->assertSame('files', $created->log_name);
        $this->assertTrue($created->causer->is($actor));
        $this->assertSame('before.pdf', $created->attribute_changes->get('attributes')['original_name']);

        $file->update(['original_name' => 'after.pdf']);
        $updated = Activity::forSubject($file)->where('event', 'updated')->sole();
        $this->assertSame('before.pdf', $updated->attribute_changes->get('old')['original_name']);
        $this->assertSame('after.pdf', $updated->attribute_changes->get('attributes')['original_name']);
        $this->assertArrayNotHasKey('path', $updated->attribute_changes->get('attributes'));
        $file->delete();
        $this->assertSame(1, Activity::forSubject($file)->where('event', 'deleted')->count());
    }

    public function test_user_events_log_profile_changes_without_credentials(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $user = User::factory()->create(['name' => 'Before', 'remember_token' => 'secret-token']);
        $created = Activity::forSubject($user)->where('event', 'created')->sole();
        $this->assertSame('users', $created->log_name);
        $this->assertTrue($created->causer->is($actor));

        $user->forceFill(['name' => 'After', 'password' => 'new-password', 'two_factor_secret' => 'secret', 'two_factor_recovery_codes' => 'codes'])->save();
        $updated = Activity::forSubject($user)->where('event', 'updated')->sole();
        $this->assertSame('Before', $updated->attribute_changes->get('old')['name']);
        $this->assertSame('After', $updated->attribute_changes->get('attributes')['name']);
        $user->delete();
        $deleted = Activity::forSubject($user)->where('event', 'deleted')->sole();

        foreach ([$created, $updated, $deleted] as $entry) {
            foreach (['attributes', 'old'] as $key) {
                foreach (['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'] as $secret) {
                    $this->assertArrayNotHasKey($secret, $entry->attribute_changes->get($key, []));
                }
            }
        }
    }

    public function test_password_only_update_does_not_create_empty_activity(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => 'changed-password']);

        $this->assertSame(0, Activity::forSubject($user)->where('event', 'updated')->count());
    }
}
