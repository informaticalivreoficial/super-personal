<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Livewire\Dashboard\Platform\ContactMessageIndex;
use App\Mail\ContactMessageMail;
use App\Models\Config;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use App\Services\ContactService;
use Database\Seeders\ConfigTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Página pública de atendimento (`/atendimento`): formulário de contato que
 * avisa a equipe por e-mail, notificação no banco (admins) e Telegram.
 */
class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfigTableSeeder::class);

        // Isola os testes de um MAIL_CONTACT_TO real no .env: o destinatário
        // padrão do teste é o e-mail das Configurações (seed).
        config(['mail.contact_to' => null]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => 1,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Souza',
            'email' => 'maria@example.com',
            'phone' => '(12) 99999-0000',
            'subject' => 'Dúvida sobre cobrança',
            'message' => 'Gostaria de entender como funciona o repasse das cobranças online.',
            'consent' => '1',
        ], $overrides);
    }

    private function configureTelegram(): void
    {
        config([
            'services.telegram.bot_token' => 'token-de-teste',
            'services.telegram.chat_id' => '-100123',
        ]);
    }

    public function test_page_renders_for_a_guest_with_channels_form_and_faq(): void
    {
        $this->get(route('web.contact'))
            ->assertOk()
            ->assertSee('Fale com a gente')
            ->assertSee('Enviar mensagem')
            ->assertSee('Dúvidas frequentes')
            ->assertSee('teste@teste.com.br')
            ->assertSee('https://wa.me/551111111111', false)
            ->assertSee(route('web.contact.store'), false)
            ->assertSee(route('web.privacy'), false);
    }

    public function test_home_and_legal_pages_link_to_the_contact_page(): void
    {
        $this->get(route('web.home'))
            ->assertOk()
            ->assertSee(route('web.contact'), false);

        $this->get(route('web.terms'))
            ->assertOk()
            ->assertSee(route('web.contact'), false);
    }

    public function test_valid_submission_sends_email_notification_and_telegram(): void
    {
        Mail::fake();
        Notification::fake();
        Http::fake();
        $this->configureTelegram();

        $admin = $this->admin();

        $this->post(route('web.contact.store'), $this->payload())
            ->assertRedirect(route('web.contact'))
            ->assertSessionHas('contact_status');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
            return $mail->hasTo('teste@teste.com.br')
                && $mail->hasReplyTo('maria@example.com')
                && $mail->data['message'] === 'Gostaria de entender como funciona o repasse das cobranças online.';
        });

        Notification::assertSentTo(
            $admin,
            ContactMessageReceived::class,
            fn (ContactMessageReceived $notification) => $notification->data['type'] === ContactMessageReceived::TYPE
                && $notification->data['email'] === 'maria@example.com'
                && $notification->data['name'] === 'Maria Souza'
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://api.telegram.org/bottoken-de-teste/sendMessage')
                && $request['chat_id'] === '-100123'
                && str_contains($request['text'], 'Maria Souza')
                && str_contains($request['text'], 'Dúvida sobre cobrança');
        });
    }

    public function test_mail_contact_to_environment_wins_over_the_settings_email(): void
    {
        Mail::fake();
        Notification::fake();

        config(['mail.contact_to' => 'equipe@superpersonal.test']);

        $this->post(route('web.contact.store'), $this->payload())->assertRedirect();

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail) => $mail->hasTo('equipe@superpersonal.test'));
    }

    public function test_submission_works_without_any_platform_admin(): void
    {
        Mail::fake();
        Notification::fake();

        $this->post(route('web.contact.store'), $this->payload())
            ->assertRedirect(route('web.contact'));

        Mail::assertSent(ContactMessageMail::class);
        Notification::assertNothingSent();
    }

    public function test_telegram_is_skipped_when_not_configured(): void
    {
        Mail::fake();
        Notification::fake();
        Http::fake();

        config([
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
        ]);

        $this->post(route('web.contact.store'), $this->payload())->assertRedirect();

        Mail::assertSent(ContactMessageMail::class);
        Http::assertNothingSent();
    }

    public function test_telegram_failure_does_not_break_the_submission(): void
    {
        Mail::fake();
        Notification::fake();
        $this->configureTelegram();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 403),
        ]);

        $this->post(route('web.contact.store'), $this->payload())
            ->assertRedirect(route('web.contact'))
            ->assertSessionHas('contact_status');

        Mail::assertSent(ContactMessageMail::class);
    }

    public function test_invalid_payload_is_rejected_without_sending_anything(): void
    {
        Mail::fake();
        Notification::fake();
        Http::fake();
        $this->configureTelegram();

        $this->post(route('web.contact.store'), [
            'name' => '',
            'email' => 'nao-e-email',
            'message' => 'curta',
        ])->assertSessionHasErrors(['name', 'email', 'message', 'consent']);

        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_honeypot_submission_is_ignored_silently(): void
    {
        Mail::fake();
        Notification::fake();
        Http::fake();

        $this->post(route('web.contact.store'), $this->payload(['website' => 'https://spam.example']))
            ->assertRedirect(route('web.contact'))
            ->assertSessionHas('contact_status');

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_submissions_are_rate_limited(): void
    {
        Mail::fake();
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('web.contact.store'), $this->payload())->assertRedirect();
        }

        $this->post(route('web.contact.store'), $this->payload())->assertStatus(429);
    }

    public function test_admin_lists_received_messages(): void
    {
        $admin = $this->admin();

        $this->post(route('web.contact.store'), $this->payload());

        $this->actingAs($admin)
            ->get(route('contact.messages.index'))
            ->assertOk()
            ->assertSee('Atendimentos')
            ->assertSee('Dúvida sobre cobrança')
            ->assertSee('maria@example.com')
            ->assertSee('Maria Souza');
    }

    public function test_teacher_cannot_open_the_contact_messages_page(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::TEACHER,
            'status' => 1,
        ]);

        $this->actingAs($teacher)
            ->get(route('contact.messages.index'))
            ->assertForbidden();
    }

    public function test_admin_can_mark_messages_as_read(): void
    {
        $admin = $this->admin();

        $this->post(route('web.contact.store'), $this->payload());

        $notification = $admin->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        Livewire::actingAs($admin)
            ->test(ContactMessageIndex::class)
            ->call('markAsRead', $notification->id)
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        // A tela do outro admin não é afetada (cada cópia é individual).
        $other = $this->admin();
        $this->assertSame(0, $other->notifications()->count());
    }

    public function test_admin_can_mark_all_messages_as_read(): void
    {
        $admin = $this->admin();

        $this->post(route('web.contact.store'), $this->payload());
        $this->post(route('web.contact.store'), $this->payload(['subject' => 'Segunda mensagem']));

        $this->assertSame(2, $admin->unreadNotifications()->count());

        Livewire::actingAs($admin)
            ->test(ContactMessageIndex::class)
            ->call('markAllAsRead')
            ->assertOk();

        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_guest_cannot_open_the_contact_messages_page(): void
    {
        $this->get(route('contact.messages.index'))->assertRedirect(route('login'));
    }

    public function test_contact_recipient_falls_back_to_the_sender_when_nothing_is_configured(): void
    {
        config([
            'mail.contact_to' => null,
            'mail.from.address' => 'remetente@superpersonal.test',
        ]);

        Config::query()->update(['email' => null]);

        $this->assertSame('remetente@superpersonal.test', app(ContactService::class)->recipient());
    }
}
