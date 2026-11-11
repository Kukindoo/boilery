<?php

namespace App\Actions\Quotes;

use App\Actions\Files\UploadQuoteFile;
use App\Actions\Users\CreateCustomer;
use App\Dtos\NamePlate;
use App\Dtos\Quotes\QuoteDto;
use App\Enums\FileTypes;
use App\Models\Issue;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateQuote
{
    use LogsActionActivity;

    /** @throws Throwable */
    public function handle(
        QuoteDto $data,
        ?UploadedFile $fileLabel = null,
        ?UploadedFile $fileReceipt = null,
        ?UploadedFile $fileWarrantyDocument = null,
    ): Quote {
        try {
            return DB::transaction(function () use ($data, $fileLabel, $fileReceipt, $fileWarrantyDocument): Quote {
                $user = app(CreateCustomer::class)->handle(new NamePlate(
                    $data->firstName, $data->lastName, $data->email, $data->phone, $data->address,
                ));
                $issue = Issue::create(['user_id' => $user->id]);
                $quote = Quote::create([...$data->toArray(), 'issue_id' => $issue->id]);

                foreach ([
                    [$fileLabel, FileTypes::BOILER_LABEL],
                    [$fileReceipt, FileTypes::RECEIPT],
                    [$fileWarrantyDocument, FileTypes::WARRANTY],
                ] as [$file, $type]) {
                    if ($file !== null) {
                        app(UploadQuoteFile::class)->handle($file, $quote, $type);
                    }
                }
                $this->logActionCompleted('quotes', $quote, 'Quote created');

                return $quote;
            });
        } catch (Throwable $exception) {
            $this->logActionAttempt('quotes', null, $exception);

            throw $exception;
        }
    }
}
