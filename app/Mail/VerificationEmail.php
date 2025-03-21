<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected User $user, protected string $verificationUrl)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email address',
            from: new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')),
            replyTo: [
                new Address(env('MAIL_REPLY_TO_ADDRESS'), env('MAIL_REPLY_TO_NAME')),
            ],
            to: [$this->user->email],
            tags: ['verification'],
            metadata: [
                'user_id' => $this->user->id,
            ],
            using: [
                // The using array contains a closure (callback function) that receives the Email object before sending. 
                // Inside this closure, you can modify the email.
                function (Email $message) {
                    // Set a custom "Reply-To" address
                    $message->replyTo(request()->input('reply_to_email', env('MAIL_REPLY_TO_ADDRESS')));

                    // Add a custom header
                    $message->getHeaders()->addTextHeader('X-Order-ID', '123456');

                    // Add an inline attachment (e.g., company logo)
                    // $logoPath = storage_path('app/public/logo.png');
                    // $message->embed($logoPath, 'logo', 'image/png');
                },
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification.emailVerification',
            with: [
                'userName' => $this->user->name,
                'verificationUrl' => $this->verificationUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath(storage_path('app/public/myfile.txt'))->as('myfile.pdf')->withMime('application/pdf'),
            // Attachment::fromStorage('public/myfile.txt')->as('my-logs.pdf')->withMime('application/pdf'),
            // Attachment::fromStorageDisk('public','public/myfile.txt')->as('my-logs.pdf')->withMime('application/pdf'),
            Attachment::fromData(function () {
                return 'hello, world';
            }, 'Report.pdf')->withMime('application/pdf'),
        ];
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            messageId: 'custom-message-id@example.com',
            references: ['previous-message@example.com'],
            text: [
                'X-Custom-Header' => 'Custom Value',
            ],
        );
    }
}