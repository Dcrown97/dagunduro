<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Messages extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name = 'Dagunduro';
        $address = env('MAIL_FROM_ADDRESS');
        $subject = 'New Message from Dagunduro';
        return $this->view('mail.message')
            ->from($address, $name)
            ->subject($subject)
            ->with([
                'contact' => $this->contact,
            ]);
    }
}
