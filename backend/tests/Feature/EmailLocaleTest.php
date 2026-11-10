<?php

namespace Tests\Feature;

use App\Enums\EmailTemplateType;
use App\Mail\SendApprovalRequestCreatedEmail;
use App\Mail\SendApprovalRequestEmail;
use App\Mail\SendApprovalRequestHandledEmail;
use App\Mail\SendConfirmEmail;
use App\Mail\SendEmailNotifcation;
use App\Mail\SendInfoUserDeletedEmail;
use App\Mail\SendNotifcationConfirm;
use App\Mail\SendOrganisationInviteToUserEmail;
use App\Mail\SendOrganisationRemovedMembershipToUserEmail;
use App\Mail\SendRequestOrganisationCreationEmail;
use App\Models\ApprovalRequest;
use App\Models\EmailTemplate;
use App\Models\Mobilizon;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Emails are rendered in the recipient's preferred language (users.locale),
 * using one admin-editable template row per event and language.
 */
class EmailLocaleTest extends TestCase
{
    use RefreshDatabase;

    private const DE_VALIDATION_SUBJECT = 'Bestätigen Sie Ihre E-Mail-Adresse, um Ihr Konto zu aktivieren';
    private const EN_VALIDATION_SUBJECT = 'Confirm your email address to activate your account';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.default_locale' => 'de', 'app.fallback_locale' => 'en']);
        App::setLocale('de');
    }

    protected function tearDown(): void
    {
        $this->setMobilizonInstance(null);

        parent::tearDown();
    }

    public function test_preferred_locale_returns_the_stored_locale(): void
    {
        $this->assertSame('en', User::factory()->make(['locale' => 'en'])->preferredLocale());
        $this->assertSame('de', User::factory()->make(['locale' => 'de'])->preferredLocale());
    }

    public function test_preferred_locale_falls_back_to_the_instance_default_not_the_request_locale(): void
    {
        // App::setLocale() (SetLocale middleware) also overwrites config('app.locale')
        App::setLocale('en');

        $this->assertSame('de', User::factory()->make(['locale' => null])->preferredLocale());
        $this->assertSame('de', User::factory()->make(['locale' => 'fr'])->preferredLocale());
    }

    public function test_templates_exist_for_every_event_in_every_supported_language(): void
    {
        $this->assertSame(count(EmailTemplateType::cases()) * 2, EmailTemplate::count());

        $this->assertSame(self::EN_VALIDATION_SUBJECT, EmailTemplate::forEvent(EmailTemplateType::VALIDATION, 'en')->subject);
        $this->assertSame(self::DE_VALIDATION_SUBJECT, EmailTemplate::forEvent(EmailTemplateType::VALIDATION, 'de')->subject);
    }

    public function test_template_lookup_falls_back_to_the_fallback_locale_and_then_to_the_defaults(): void
    {
        EmailTemplate::where('on_event', EmailTemplateType::VALIDATION)->where('locale', 'de')->delete();
        EmailTemplate::where('on_event', EmailTemplateType::VALIDATION)->where('locale', 'en')
            ->update(['subject' => 'Edited English subject']);

        $this->assertSame('Edited English subject', EmailTemplate::forEvent(EmailTemplateType::VALIDATION, 'de')->subject);

        EmailTemplate::where('on_event', EmailTemplateType::VALIDATION)->delete();

        $template = EmailTemplate::forEvent(EmailTemplateType::VALIDATION, 'de');
        $this->assertFalse($template->exists);
        $this->assertSame(self::DE_VALIDATION_SUBJECT, $template->subject);
    }

    public function test_mailables_render_template_and_surrounding_text_in_the_requested_locale(): void
    {
        $english = (new SendConfirmEmail('token-123'))->locale('en');
        $english->assertSeeInHtml('Confirm email address');
        $english->assertSeeInHtml('/validate/token-123', false);
        $english->assertSeeInHtml('<html lang="en">', false);
        $english->assertDontSeeInHtml('bestätigen');
        $english->assertDontSeeInHtml('Nutzungsbedingungen');
        // the subject was hydrated within the mail's locale by the render above
        $english->assertHasSubject(self::EN_VALIDATION_SUBJECT);

        $german = (new SendConfirmEmail('token-123'))->locale('de');
        $german->assertSeeInHtml('E-Mail-Adresse bestätigen');
        $german->assertHasSubject(self::DE_VALIDATION_SUBJECT);
    }

    public function test_admin_edits_of_a_template_only_affect_that_language(): void
    {
        config(['app.name' => 'DSG']);
        EmailTemplate::where('on_event', EmailTemplateType::VALIDATION)->where('locale', 'en')
            ->update(['subject' => 'Welcome to :appName']);

        $english = (new SendConfirmEmail('t'))->locale('en');
        $english->render();
        $english->assertHasSubject('Welcome to DSG');

        $german = (new SendConfirmEmail('t'))->locale('de');
        $german->render();
        $german->assertHasSubject(self::DE_VALIDATION_SUBJECT);
    }

    public function test_emails_to_a_user_use_the_recipient_language_not_the_request_language(): void
    {
        // e.g. a German speaking admin triggers an email to an English speaking user
        App::setLocale('de');
        $user = User::factory()->create(['locale' => 'en']);

        Mail::to($user)->send(new SendConfirmEmail('t'));

        $this->assertSame([self::EN_VALIDATION_SUBJECT], $this->sentSubjects());
        // the locale of the request is restored afterwards
        $this->assertSame('de', App::getLocale());
    }

    public function test_approval_request_variables_are_translated_into_the_recipient_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $approvalRequest = ApprovalRequest::create([
            'requestable_type' => 'SingleEvent',
            'request_type' => 'store',
            'payload' => [],
            'status' => 'approved',
            'created_by_user_id' => $user->id,
        ]);

        Mail::to($user)->send(new SendApprovalRequestHandledEmail($approvalRequest));

        $this->assertSame(['Your approval request has been processed: approved'], $this->sentSubjects());

        $html = (new SendApprovalRequestHandledEmail($approvalRequest))->locale('en')->render();
        $this->assertStringContainsString('single event creation', $html);
        $this->assertStringContainsString('Approved', $html);
        $this->assertStringNotContainsString('Genehmigt', $html);

        $html = (new SendApprovalRequestHandledEmail($approvalRequest))->locale('de')->render();
        $this->assertStringContainsString('Einzeltermin erstellen', $html);
        $this->assertStringContainsString('Genehmigt', $html);
    }

    public function test_every_email_renders_without_missing_translations_in_every_language(): void
    {
        $user = User::factory()->create(['locale' => 'en', 'mobilizon_name' => 'Jane']);
        $approvalRequest = ApprovalRequest::create([
            'requestable_type' => 'Organisation',
            'request_type' => 'changeOrganisationStatus',
            'payload' => [],
            'status' => 'rejected',
            'admin_comment' => 'Not yet',
            'created_by_user_id' => $user->id,
        ]);
        $event = [
            'uuid' => 'abc',
            'title' => 'Concert',
            'formattedBeginsOn' => 'Mon 01.01.2027 10:00',
            'attributedTo' => ['name' => 'Org'],
        ];
        $events = ['thisWeek' => ['events' => [$event, $event], 'alternativeEvents' => []]];

        $mailables = fn () => [
            new SendConfirmEmail('t'),
            new SendApprovalRequestCreatedEmail($approvalRequest),
            new SendApprovalRequestHandledEmail($approvalRequest),
            new SendApprovalRequestEmail($approvalRequest),
            new SendOrganisationInviteToUserEmail('Org'),
            new SendOrganisationRemovedMembershipToUserEmail('Org'),
            new SendInfoUserDeletedEmail(),
            new SendNotifcationConfirm('t'),
            new SendEmailNotifcation(1, 't', $events, 'WEEKLY'),
            new SendRequestOrganisationCreationEmail('Org'),
        ];

        foreach (['de', 'en'] as $locale) {
            foreach ($mailables() as $mailable) {
                $html = $mailable->locale($locale)->render();
                $subject = $mailable->subject;
                $name = class_basename($mailable) . " ($locale)";

                $this->assertNotEmpty($subject, $name);
                $this->assertDoesNotMatchRegularExpression('/\bemails\.[a-zA-Z]/', $html . $subject, $name);
                $this->assertStringNotContainsString(':appName', $html . $subject, $name);

                if ($locale === 'en') {
                    $this->assertDoesNotMatchRegularExpression('/Moin|Sie |Ihre |Termin|Datenschutz/', $html . $subject, $name);
                }
            }
        }
    }

    public function test_password_reset_notification_uses_the_user_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $user->notify(new CustomResetPassword('reset-token'));

        $this->assertSame(['Reset password'], $this->sentSubjects());
    }

    public function test_profile_update_stores_the_email_language(): void
    {
        $this->fakeMobilizon();
        $user = User::factory()->create(['locale' => 'de', 'mobilizon_name' => 'Jane']);

        $this->actingAs($user, 'api')
            ->withHeader('Accept-Language', 'de')
            ->postJson('/dsgapi/user/updateProfile', [
                'email' => $user->email,
                'mobilizon_name' => 'Jane',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('user.locale', 'en')
            // the response itself stays in the UI language
            ->assertJsonPath('message', __('messages.users.profileUpdated', [], 'de'));

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_profile_update_rejects_unsupported_languages(): void
    {
        $this->fakeMobilizon();
        $user = User::factory()->create(['locale' => 'de', 'mobilizon_name' => 'Jane']);

        $this->actingAs($user, 'api')
            ->postJson('/dsgapi/user/updateProfile', [
                'email' => $user->email,
                'mobilizon_name' => 'Jane',
                'locale' => 'fr',
            ])
            ->assertStatus(422);

        $this->assertSame('de', $user->fresh()->locale);
    }

    public function test_profile_update_keeps_the_email_language_when_the_field_is_omitted(): void
    {
        $this->fakeMobilizon();
        $user = User::factory()->create(['locale' => 'en', 'mobilizon_name' => 'Jane']);

        $this->actingAs($user, 'api')
            ->postJson('/dsgapi/user/updateProfile', [
                'email' => $user->email,
                'mobilizon_name' => 'Jane',
            ])
            ->assertOk();

        $this->assertSame('en', $user->fresh()->locale);
    }

    /**
     * Subjects of all emails sent through the array mailer (MAIL_MAILER=array in phpunit.xml).
     */
    private function sentSubjects(): array
    {
        return collect(Mail::getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage()->getSubject())
            ->all();
    }

    /**
     * Skips the real Mobilizon login: updateProfile() only reads ->person from the singleton
     * as long as the name is unchanged.
     */
    private function fakeMobilizon(): void
    {
        $mobilizon = new Mobilizon();
        $mobilizon->person = ['name' => 'Jane'];
        $this->setMobilizonInstance($mobilizon);
    }

    private function setMobilizonInstance(?Mobilizon $instance): void
    {
        (new ReflectionProperty(Mobilizon::class, 'instance'))->setValue(null, $instance);
    }
}
