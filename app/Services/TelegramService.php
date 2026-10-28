<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Avisos instantâneos pelo Telegram (bot da plataforma).
 *
 * Usado pela página de atendimento para avisar a equipe assim que uma
 * mensagem chega. O canal é opcional: sem `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID`
 * nada é enviado e a operação devolve `false` — o formulário continua sendo
 * atendido pelo e-mail e pela notificação no banco.
 *
 * Falha do Telegram nunca derruba o fluxo do visitante: exceção é reportada e
 * o retorno é `false`.
 */
class TelegramService
{
    /** Motivo da última falha (diagnóstico: `php artisan telegram:test`). */
    private ?string $lastError = null;

    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'))
            && filled(config('services.telegram.chat_id'));
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Dados do bot (`getMe`) — confirma token válido e mostra o @username.
     *
     * @return array<string, mixed>|null
     */
    public function me(): ?array
    {
        if (! filled(config('services.telegram.bot_token'))) {
            $this->lastError = 'TELEGRAM_BOT_TOKEN não configurado.';

            return null;
        }

        try {
            $response = Http::timeout((int) config('services.telegram.timeout', 8))
                ->get($this->apiUrl().'/getMe');

            if ($response->failed()) {
                $this->lastError = $response->json('description') ?? 'HTTP '.$response->status();

                return null;
            }

            return (array) $response->json('result', []);
        } catch (Throwable $exception) {
            report($exception);
            $this->lastError = $exception->getMessage();

            return null;
        }
    }

    /**
     * Chats que falaram com o bot nas últimas 24h (`getUpdates`) — ajuda a
     * descobrir o id certo quando o envio devolve "chat not found".
     *
     * @return array<int, array{id: int|string, type: string, name: string}>
     */
    public function chats(): array
    {
        try {
            $response = Http::timeout((int) config('services.telegram.timeout', 8))
                ->get($this->apiUrl().'/getUpdates');

            $chats = [];

            foreach ((array) $response->json('result', []) as $update) {
                $chat = $update['message']['chat']
                    ?? $update['my_chat_member']['chat']
                    ?? $update['channel_post']['chat']
                    ?? null;

                if (! is_array($chat)) {
                    continue;
                }

                $chats[(string) $chat['id']] = [
                    'id' => $chat['id'],
                    'type' => $chat['type'] ?? '-',
                    'name' => $chat['title'] ?? $chat['username'] ?? $chat['first_name'] ?? '-',
                ];
            }

            return array_values($chats);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * Envia uma mensagem em HTML (parse_mode=HTML) para o chat configurado.
     *
     * @param  string  $message  Texto já escapado quando vier de dados do visitante.
     */
    public function send(string $message, ?string $chatId = null): bool
    {
        if (! $this->isConfigured()) {
            $this->lastError = 'Telegram desligado: informe TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID.';

            return false;
        }

        $this->lastError = null;

        $chatId = trim((string) ($chatId ?: config('services.telegram.chat_id')));

        try {
            $response = Http::timeout((int) config('services.telegram.timeout', 8))
                ->asForm()
                ->post($this->apiUrl().'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => 'true',
                ]);

            if ($response->failed()) {
                $this->lastError = $response->json('description') ?? 'HTTP '.$response->status();

                Log::warning('Telegram recusou o envio da mensagem.', [
                    'status' => $response->status(),
                    'body' => $this->lastError,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            // Rede/timeout: reporta e segue — o visitante não pode ver erro.
            report($exception);
            $this->lastError = $exception->getMessage();

            return false;
        }
    }

    private function apiUrl(): string
    {
        $baseUrl = rtrim((string) config('services.telegram.base_url', 'https://api.telegram.org'), '/');

        return $baseUrl.'/bot'.trim((string) config('services.telegram.bot_token'));
    }
}
