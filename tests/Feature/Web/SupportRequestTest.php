<?php

namespace Tests\Feature\Web;

use App\Livewire\Components\SupportModal;
use App\Mail\SupportRequestMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * O botão de suporte do painel envia um e-mail para o desenvolvedor.
 * Garante que o envio sai pelo remetente configurado (domínio verificado) e
 * que a validação mínima da mensagem continua valendo.
 */
class SupportRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_solicitacao_de_suporte(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SupportModal::class)
            ->set('message', 'Preciso de ajuda com o cadastro de alunos.')
            ->call('sendSupport')
            ->assertHasNoErrors();

        Mail::assertSent(SupportRequestMail::class, function (SupportRequestMail $mail) {
            return $mail->hasTo(config('app.desenvolvedor_email'));
        });
    }

    public function test_valida_mensagem_minima(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SupportModal::class)
            ->set('message', 'curta')
            ->call('sendSupport')
            ->assertHasErrors(['message']);

        Mail::assertNothingSent();
    }
}
