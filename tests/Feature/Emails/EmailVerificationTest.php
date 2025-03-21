<?php

namespace Tests\Feature\Emails;

use App\Mail\ActivationEmail;
use Faker\Factory;
use Tests\TestCase;
use App\Models\User;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    public function test_mailable_content(): void
    {
        $user = User::factory()->create();
        $verificationURL = Factory::create()->url();
        $mailable = new VerificationEmail($user, $verificationURL);
        
        $mailable->assertFrom(env('MAIL_FROM_ADDRESS'));
        $mailable->assertHasReplyTo(env('MAIL_REPLY_TO_ADDRESS'));
        $mailable->assertTo($user->email);
        // $mailable->assertHasCc('');
        // $mailable->assertHasBcc('');
        
        
        // $mailable->assertHasTag();
        // $mailable->assertHasMetadata();
        
        // $mailable->assertHasAttachment('/path/to/file');
        // $mailable->assertHasAttachment(Attachment::fromPath('/path/to/file'));
        // $mailable->assertHasAttachedData($pdfData, 'name.pdf', ['mime' => 'application/pdf']);
        // $mailable->assertHasAttachmentFromStorage('/path/to/file', 'name.pdf', ['mime' => 'application/pdf']);
        // $mailable->assertHasAttachmentFromStorageDisk('s3', '/path/to/file', 'name.pdf', ['mime' => 'application/pdf']);
        
        $mailable->assertHasSubject('Verify your email address');
        $mailable->assertSeeInHtml($user->username);
        $mailable->assertSeeInHtml($verificationURL);
    }

    public function test_email_verification_service_sends_verifivcation_email()
    {
        // Fake the mailer to prevent actual emails
        Mail::fake();

        // Create a user with a verification code
        $user = User::factory()->create();

        // Act: Simulate a request to // /api/auth/register
        $this->postJson('/api/auth/register', [
            'username'=> 'mo',
            'email'=>'test@gmail.com',
            'password'=>'Mo#Es.com123',
            'password_confirmation'=>'Mo#Es.com123',
            ])->assertStatus(201);
            // Act: Simulate a request to // /api/auth/email/resend-email
        $this->actingAs($user)->postJson('/api/auth/email/resend-email')->assertOk();

        // Assert: Check that the email was sent
        Mail::assertSent(VerificationEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Assert: Ensure only 1 email was sent
        Mail::assertSent(VerificationEmail::class, 2);

        // Assert: No unexpected emails were sent
        // Mail::assertNothingOutgoing();
    }

    public function test_email_verification_service_sends_activation_email()
    {
        // Fake the mailer to prevent actual emails
        Mail::fake();

        // Create a user with a verification code
        $code = '123456';
        $user = User::factory()->create();
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = now()->addMinutes(10);
        $user->save();

        // Act: Simulate a request to verify the code
        $this->actingAs($user)->postJson('/api/auth/email/submit-code', [
            'code' => $code,
        ])->assertOk();

        // Assert: Check that the email was sent
        Mail::assertSent(ActivationEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Assert: Ensure only 1 email was sent
        Mail::assertSent(ActivationEmail::class, 1);

        // Assert: No unexpected emails were sent
        // Mail::assertNothingOutgoing();
    }
}