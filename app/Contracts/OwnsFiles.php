<?php

namespace App\Contracts;

use App\Models\File;
use App\Models\FileAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/** @phpstan-require-extends Model */
interface OwnsFiles
{
    /** @return MorphToMany<File, covariant Model, FileAttachment> */
    public function files(): MorphToMany;

    public function isFileOwner(User $user): bool;
}
