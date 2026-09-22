<?php

namespace App\Actions\Quotes;

use App\Actions\Files\UploadQuoteFile;
use App\Enums\FileTypes;
use App\Models\RequestedQuotes;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateQuote
{
    /**
     * @throws Throwable
     */
    public function handle($form, User $user): RequestedQuotes
    {
        return DB::transaction(function () use ($form, $user) {
            $quote = RequestedQuotes::create([
                'first_name' => $form->firstName,
                'last_name' => $form->lastName,
                'user_id' => $user->id,
                'email' => $form->email,
                'phone' => $form->phone,
                'message' => $form->message,
                'address' => $form->address ?? null,
                'under_warranty' => $form->boilerUnderWarranty === 'yes',
                'boiler_manufacturer' => $form->boilerManufacturer,
                'boiler_serial_number' => $form->boilerSerialNumber ?? null,
                'boiler_type' => $form->boilerType ?? null,
            ]);

            if ($form->fileLabel) {
                $file = $form->fileLabel;

                app(UploadQuoteFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::BOILER_LABEL);
            }

            if ($form->fileReceipt) {
                $file = $form->fileReceipt;

                app(UploadQuoteFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::RECEIPT);
            }

            if ($form->fileWarrantyDocument) {
                $file = $form->fileWarrantyDocument;

                app(UploadQuoteFile::class)->handle(
                    $file,
                    $quote,
                    FileTypes::WARRANTY);
            }

            return $quote;
        });
    }
}
