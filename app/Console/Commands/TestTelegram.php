<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Diagnóstico do canal Telegram: confirma o token (`getMe`), mostra o bot e
 * envia uma mensagem de teste para o chat configurado — útil para descobrir
 * "chat not found" (bot que ninguém iniciou, id errado ou bot fora do grupo).
 */
class TestTelegram extends Command
{
    protected $signature = 'telegram:test {chat_id? : Chat de destino (padrão: TELEGRAM_CHAT_ID)}';

    protected $description = 'Testa a integração do Telegram (token, bot e envio de mensagem).';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isConfigured()) {
            $this->error('Telegram desligado: configure TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID no .env.');

            return self::FAILURE;
        }

        $bot = $telegram->me();

        if ($bot === null) {
            $this->error('Token inválido ou API inacessível: '.$telegram->lastError());

            return self::FAILURE;
        }

        $this->info('Bot: @'.($bot['username'] ?? '?').' ('.($bot['first_name'] ?? '').')');
        $this->line('Destino: '.($this->argument('chat_id') ?: config('services.telegram.chat_id')));

        $sent = $telegram->send(
            '✅ <b>Teste de integração</b> — o canal de atendimento está funcionando.',
            $this->argument('chat_id') ?: null
        );

        if (! $sent) {
            $this->error('Falha no envio: '.$telegram->lastError());

            if (str_contains((string) $telegram->lastError(), 'chat not found')) {
                $this->newLine();
                $this->warn('O bot não pode iniciar conversa: abra o Telegram, procure @'.($bot['username'] ?? 'o bot')
                    .' e toque em "Iniciar" (ou adicione o bot ao grupo e envie uma mensagem lá).');
                $this->line('Depois disso, rode o teste de novo ou use o id mostrado em getUpdates.');

                $chats = $telegram->chats();

                if ($chats !== []) {
                    $this->newLine();
                    $this->line('Chats que falaram com o bot recentemente:');

                    foreach ($chats as $chat) {
                        $this->line('  • id '.$chat['id'].' — '.$chat['type'].' ('.$chat['name'].')');
                    }
                } else {
                    $this->line('Nenhuma conversa encontrada: envie /start para o bot e rode o teste novamente.');
                }
            }

            return self::FAILURE;
        }

        $this->info('Mensagem enviada com sucesso.');

        return self::SUCCESS;
    }
}
