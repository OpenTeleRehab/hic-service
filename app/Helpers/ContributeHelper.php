<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;

class ContributeHelper
{
    /**
     * @param string $email
     * @param string $first_name
     * @param string $comfirmationLink
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function sendEmailNotification($email, $first_name, $comfirmationLink)
    {
        $data = [
            'subject' => 'OpenTeleRehab Library Resource Submission Confirmation',
            'email' => $email,
            'first_name' => $first_name,
            'url' => $comfirmationLink,
        ];

        Mail::send('emails.submission_confirmation', $data, function ($message) use ($data) {
            $message->to($data['email'])->subject($data['subject']);
        });

        return back()->with(['message' => 'Email successfully sent!']);
    }
}
