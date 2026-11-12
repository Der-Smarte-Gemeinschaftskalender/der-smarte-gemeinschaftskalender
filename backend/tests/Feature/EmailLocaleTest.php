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
use App\Models\Notification;
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

        config([
            'app.supported_locales' => ['de', 'en'],
            'app.fallback_locale' => 'en',
            'app.localized_names' => [],
        ]);
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

    public function test_preferred_locale_is_null_for_missing_or_disabled_languages(): void
    {
        // null makes Laravel keep the current locale (request language, in the console APP_LOCALE)
        $this->assertNull(User::factory()->make(['locale' => null])->preferredLocale());
        $this->assertNull(User::factory()->make(['locale' => 'fr'])->preferredLocale());

        config(['app.supported_locales' => ['de']]);
        $this->assertNull(User::factory()->make(['locale' => 'en'])->preferredLocale());
    }

    public function test_emails_to_a_user_without_a_supported_language_use_the_current_locale(): void
    {
        App::setLocale('en');
        $user = User::factory()->create(['locale' => 'fr']);

        Mail::to($user)->send(new SendConfirmEmail('t'));

        $this->assertSame([self::EN_VALIDATION_SUBJECT], $this->sentSubjects());
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

    public function test_admin_template_list_only_contains_enabled_languages(): void
    {
        config(['app.supported_locales' => ['de']]);
        $admin = User::factory()->create(['type' => 'admin']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/dsgapi/email-templates?pageSize=100')
            ->assertOk()
            ->assertJsonPath('total', count(EmailTemplateType::cases()));

        $this->assertSame(['de'], collect($response->json('data'))->pluck('locale')->unique()->values()->all());
    }

    public function test_app_name_can_be_set_per_language(): void
    {
        config(['app.name' => 'DSG', 'app.localized_names' => ['en' => 'Smart Calendar']]);
        EmailTemplate::where('on_event', EmailTemplateType::VALIDATION)
            ->update(['subject' => 'Welcome to :appName']);

        $english = (new SendConfirmEmail('t'))->locale('en');
        $english->render();
        $english->assertHasSubject('Welcome to Smart Calendar');

        $german = (new SendConfirmEmail('t'))->locale('de');
        $german->render();
        $german->assertHasSubject('Welcome to DSG');

        $deleted = (new SendInfoUserDeletedEmail())->locale('en');
        $deleted->assertSeeInHtml('<title>Smart Calendar</title>', false);
        $deleted->assertDontSeeInHtml('DSG');
        $deleted->assertHasSubject('Deletion of your Smart Calendar account');
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

    public function test_newsletter_subscription_stores_the_request_language(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->postJson('/dsgapi/notifications', $this->subscriptionPayload('reader@example.com'))
            ->assertCreated();

        $this->assertSame('en', Notification::where('email', 'reader@example.com')->value('locale'));
        // the confirmation email is sent in the request language as well
        $this->assertSame([__('emails.subjects.notificationConfirm', [], 'en')], $this->sentSubjects());
    }

    public function test_requests_in_a_disabled_language_use_the_default_language(): void
    {
        config(['app.supported_locales' => ['de']]);

        $this->withHeader('Accept-Language', 'en')
            ->postJson('/dsgapi/notifications', $this->subscriptionPayload('reader@example.com'))
            ->assertCreated()
            ->assertJsonPath('message', __('messages.notifications.subscribed', [], 'de'));

        $this->assertSame('de', Notification::where('email', 'reader@example.com')->value('locale'));
    }

    public function test_newsletter_is_sent_in_the_language_of_the_subscription(): void
    {
        $event = [
            'uuid' => 'abc',
            'title' => 'Concert',
            'beginsOn' => '2027-01-04T10:00:00Z',
            'attributedTo' => ['id' => '1', 'name' => 'Org'],
        ];
        // the newsletter template skips the first event of each section
        $this->fakeMobilizonSearch([$event, $event]);

        foreach (['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'none' => null] as $name => $locale) {
            Notification::forceCreate([
                'email' => "$name@example.com",
                'locale' => $locale,
                'intervall' => 'WEEKLY',
                'category' => 'ALL',
                'token' => "token-$name",
                'is_verified' => true,
            ]);
        }

        $this->artisan('app:send-email-nofifications', ['--intervall' => 'WEEKLY'])->assertSuccessful();

        $sent = collect(Mail::getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->keyBy(fn ($message) => $message->getTo()[0]->getAddress());

        $this->assertSame('Event notification', $sent['en@example.com']->getSubject());
        $this->assertStringContainsString('04/01/2027 10:00 AM', $sent['en@example.com']->getHtmlBody());
        $this->assertStringNotContainsString('Uhr', $sent['en@example.com']->getHtmlBody());

        // unknown, disabled or missing languages use the current locale
        foreach (['de', 'fr', 'none'] as $name) {
            $this->assertSame('Benachrichtigung Termine', $sent["$name@example.com"]->getSubject(), $name);
            $this->assertStringContainsString('04.01.2027 10:00 Uhr', $sent["$name@example.com"]->getHtmlBody(), $name);
        }

        $this->assertSame('de', App::getLocale());
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

    /**
     * Answers every event search with the given events (SendEmailNotifications uses the admin singleton).
     */
    private function fakeMobilizonSearch(array $events): void
    {
        $this->setMobilizonInstance(new class ($events) extends Mobilizon {
            public function __construct(private array $events)
            {
            }

            public function searchEvents(array $fieldsToSearch, $limit = null): array
            {
                return ['data' => ['searchEvents' => ['total' => count($this->events), 'elements' => $this->events]]];
            }
        });
    }

    private function subscriptionPayload(string $email): array
    {
        return [
            'email' => $email,
            'intervall' => 'WEEKLY',
            'category' => 'ALL',
            'eventType' => 'INTERNAL',
            'organisation' => 'ALL',
        ];
    }

    private function setMobilizonInstance(?Mobilizon $instance): void
    {
        (new ReflectionProperty(Mobilizon::class, 'instance'))->setValue(null, $instance);
    }
}
