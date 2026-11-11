<?php

use App\Models\Issue;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['quotes' => (new Quote)->getMorphClass(), 'works' => (new Work)->getMorphClass()] as $table => $type) {
                DB::table('fileables')->where('fileable_type', $type)->orderBy('id')
                    ->chunkById(500, function ($attachments) use ($table): void {
                        foreach ($attachments as $attachment) {
                            $issueId = DB::table($table)->where('id', $attachment->fileable_id)->value('issue_id');
                            if ($issueId === null) {
                                throw new RuntimeException('Cannot migrate an attachment without an Issue.');
                            }
                            DB::table('fileables')->insertOrIgnore([
                                'file_id' => $attachment->file_id,
                                'fileable_type' => (new Issue)->getMorphClass(),
                                'fileable_id' => $issueId,
                                'created_at' => $attachment->created_at,
                                'updated_at' => $attachment->updated_at,
                            ]);
                            DB::table('fileables')->where('id', $attachment->id)->delete();
                        }
                    });
            }
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->dropForeign(['quote_id']);
            $table->dropUnique(['quote_id']);
            $table->dropColumn('quote_id');
        });
    }
};
