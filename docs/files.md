Created: 2026-11-06
Last Modified: 2026-11-07

<!-- 2026-11-07: Document current behavior and apply Markdown formatting conventions. -->
# Shared file attachments

A `File` represents one stored document. Metadata and the storage path remain in `files`.
`fileables` links that file to any number of models using `file_id`, `fileable_type`, and
`fileable_id`. A unique constraint prevents duplicate links, and deleting a File cascades to its
pivot rows.

Quote, Work, and User expose `files()` through `App\Traits\HasFiles`.

<!-- 2026-11-07: Describe current attachment relationships and uploads. -->
## Relationships

```php
$file = $quote->files()->create($attributes);
$work->files()->syncWithoutDetaching([$file->id]);
$user->files()->syncWithoutDetaching([$file->id]);

$file->quotes; // all attached quotes
$file->works;  // all attached works
$file->users;  // all directly attached users

foreach ($file->attachments()->with('fileable')->get() as $attachment) {
    $parent = $attachment->fileable; // any supported model type
}
```

These are Eloquent persistence APIs; user-facing calls must still enforce parent/file
authorization. Use syncWithoutDetaching to share an existing file without removing earlier links.
Creating a new File and attaching it are two writes: put both in a transaction. The upload/PDF
actions do this and clean up stored bytes when record/link creation or an enclosing transaction
rolls back.

Quote acceptance shares existing quote files with the resulting work without duplicating documents
or removing quote attachments. Authenticated uploads use `UploadFile::handle($upload, $quoteOrWork,
$fileType)`: it checks the parent's upload policy, locks quote then work, and attaches one newly
stored File to both related models. Standalone quotes/works receive one attachment. A changed
work/quote association during lock acquisition is rejected and can be retried.

Work pages offer uploads in the attachments header, including when empty, and in each WorkFileCard
menu. Work and quote uploads reuse the same configured MIME/size validation. Work upload permission
requires work edit permission plus file-create-all or file-create-own for that customer. The
UploadQuoteFile wrapper is reserved for trusted public quote creation; user-facing uploads use the
authorized shared action.

<!-- 2026-11-07: Clarify label generation and file type updates. -->
## Labels and file types

`GenerateFileLabel::handle()` accepts a customer, record ID, file type, and optional extension.
It generates labels in the format `{customer_name}_{id}_{type}.{extension}`. Customer names are
converted to ASCII, with spaces and hyphens replaced by underscores. The type suffix comes from
`FileTypes::snake()`. The extension, including its preceding dot, is omitted when empty or null.

Uploads use the ID of the Quote or Work receiving the upload. Sharing the resulting File with a
related model preserves that label. Quote summary PDFs use the quote ID; service protocol PDFs use
the work ID. For example, a summary for quote 42 belonging to Jiří Novák is named
`Jiri_Novak_42_shrnuti_poptavky.pdf`.

Uploaded files retain their client filename in `original_name`; generated PDFs use the generated
label for both `original_name` and `label`. The storage path is independent of the label. Quote and
Work downloads use `label` as the download filename.

`ChangeFileType::handle(File $file, FileTypes $fileType)` locks and reloads the File, authorizes
its update policy, and changes only `file_type` inside a transaction. Its label, original filename,
storage path, and attachment links stay unchanged, so a label's type suffix can differ from the
current type. Every attached model sees the updated type because they share the same File record.
Successful changes record the previous and new types in the `files` activity log; failures record
an attempted action and rethrow the exception.

<!-- 2026-11-07: Document the ownership contract for additional models. -->
## Extending to another model

Implement OwnsFiles and use HasFiles; define ownership for that model. File needs no additional
inverse relationship for generic attachment enumeration.

```php
use App\Contracts\OwnsFiles;
use App\Models\User;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;

class Project extends Model implements OwnsFiles
{
    use HasFiles;

    public function isFileOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
```

OwnsFiles defines the ownership check used by “own files” permissions; it does not replace the
model's normal policies. Parents that do not implement the ownership contract, or cannot be found,
do not confer ownership. Soft-deleting a parent preserves its attachments for restoration.
Hard-deleting it through Eloquent detaches its links and preserves shared File records. Bulk query
deletes bypass model events and require explicit pivot cleanup.

Morph types currently use Laravel's default model class names. If a morph map is introduced later,
migrate existing pivot type values as part of that change.

<!-- 2026-11-07: Describe current shared access and cleanup behavior. -->
## Shared access and deletion

- Read-own permission applies when the user owns any attached parent. Direct User attachments
  belong to that user; Quote/Work attachments belong to their customer.
- Global file edits/deletion with own-file permissions require ownership of every linked parent.
  All-file permissions retain their existing broad scope. Changing a File's type or metadata
  affects every attachment of that file.
- Removing an attachment from a quote/work page uses `DetachFile::handle($parent, $file)`. It
  authorizes the parent and detach permission, locks parent then file, removes only that pivot row,
  and records activity.
- After detaching, the action checks all remaining attachment types using a locking read. If any
  link remains, the File and its bytes are retained. If none remains, the File row is deleted in
  the transaction and DeleteStoredFile is queued after commit to remove the bytes. Caller rollback
  restores the row/link and discards cleanup. Storage failures retry up to five times; cleanup is
  idempotent and retains paths still referenced by another File row.
- Explicit global `DeleteFile` remains available for deliberate physical deletion of a shared file;
  it removes all links via the foreign-key cascade. Parent hard-deletion through HasFiles still
  detaches directly; last-link orphan cleanup described above belongs to the DetachFile action.
