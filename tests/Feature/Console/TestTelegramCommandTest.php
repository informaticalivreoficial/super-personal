<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `php artisan telegram:test` — diagnóstico do canal de avisos do atendimento.
 */
class TestTelegramCommandTest extends TestCase
{
    private function configure(string $token = 'token-teste', string $chatId = '123456'): void
    {
        config([
            'services.telegram.bot_token' => $token,
            'services.telegram.chat_id' => $chatId,
        ]);
    }

    public function test_fails_when_the_channel_is_not_configured(): void
    {
        config([
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
        ]);

        $this->artisan('telegram:test')
            ->expectsOutputToContain('Telegram desligado')
            ->assertFailed();
    }

    public function test_fails_when_the_token_is_rejected(): void
    {
        $this->configure();

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $this->artisan('telegram:test')
            ->expectsOutputToContain('Token inválido')
            ->assertFailed();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_sends_a_test_message_when_everything_is_configured(): void
    {
        $this->configure();

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => ['username' => 'super_personal_bot', 'first_name' => 'Super Personal'],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->artisan('telegram:test')
            ->expectsOutputToContain('@super_personal_bot')
            ->expectsOutputToContain('Mensagem enviada com sucesso')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '123456');
    }

    public function test_explains_chat_not_found(): void
    {
        $this->configure();

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => ['username' => 'super_personal_bot', 'first_name' => 'Super Personal'],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: chat not found',
            ], 400),
            'api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'message' => [
                        'chat' => ['id' => 7721123456, 'type' => 'private', 'first_name' => 'Renato'],
                    ],
                ]],
            ]),
        ]);

        $this->artisan('telegram:test')
            ->expectsOutputToContain('chat not found')
            ->expectsOutputToContain('toque em "Iniciar"')
            ->expectsOutputToContain('7721123456')
            ->assertFailed();
    }
}
