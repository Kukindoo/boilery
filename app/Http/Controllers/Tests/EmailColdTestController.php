<?php

namespace App\Http\Controllers\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\EmailColdTestRequest;
use App\Notifications\Tests\EmailColdTestNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Notification;

class EmailColdTestController extends Controller
{
    public function __invoke(EmailColdTestRequest $request)
    {
        Notification::route('mail', config('contacts.admin_email'))
            ->notify(new EmailColdTestNotification());

        return redirect()->back()->with('status', 'Test email sent successfully.');
    }
}
