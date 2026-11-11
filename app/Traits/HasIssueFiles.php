<?php

namespace App\Traits;

use App\Models\File;
use App\Models\FileAttachment;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasIssueFiles
{
    /** @return MorphToMany<File, Issue, FileAttachment> */
    public function files(): MorphToMany
    {
        // Use issue_id as the parent key so eager loading also matches by Issue.
        $issue = new Issue;
        $issue->setConnection($this->getConnectionName());
        $issue->setAttribute('issue_id', $this->issue_id);

        return $issue->morphToMany(File::class, 'fileable', parentKey: 'issue_id')
            ->using(FileAttachment::class)
            ->withTimestamps();
    }
}
