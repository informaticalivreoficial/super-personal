<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\Config;
use App\Services\ContactService;
use App\Support\Seo;

/**
 * Página pública de atendimento (`/atendimento`).
 *
 * Server-rendered (sem Livewire) para manter o SEO do site institucional, no
 * mesmo padrão visual da home e das páginas legais. O envio da mensagem é um
 * POST clássico com CSRF, validação por Form Request e throttle; o visitante
 * recebe a confirmação mesmo quando um canal de aviso falha (o ContactService
 * isola e-mail, notificação no banco e Telegram).
 */
class ContactController extends Controller
{
    private Config $config;

    public function __construct()
    {
        $this->config = Config::where('id', 1)->firstOrFail();
    }

    public function show()
    {
        $template = $this->config->template ?: 'default';

        $head = (new Seo)->render(
            'Atendimento — '.($this->config->app_name ?? config('app.name')),
            'Fale com a equipe do '.($this->config->app_name ?? config('app.name')).': dúvidas sobre planos, cobranças, acesso ao app e suporte.',
            route('web.contact'),
            $this->config->getmetaimg() ?? url(asset('images/image.jpg'))
        );

        return view('web.'.$template.'.contact', [
            'head' => $head,
            'channels' => $this->channels(),
            'faqs' => $this->faqs(),
        ]);
    }

    public function store(StoreContactMessageRequest $request, ContactService $service)
    {
        // Honeypot anti-spam: só um robô preenche o campo invisível. A resposta
        // é a mesma do envio real, de propósito.
        if (filled($request->input('website'))) {
            return redirect()
                ->route('web.contact')
                ->with('contact_status', 'Mensagem enviada! Respondemos pelo e-mail informado.');
        }

        $service->send($request->validated());

        return redirect()
            ->route('web.contact')
            ->with('contact_status', 'Mensagem enviada! Respondemos pelo e-mail informado em até 1 dia útil.');
    }

    /**
     * Canais de contato cadastrados em Configurações (só os preenchidos).
     *
     * @return array<int, array{label: string, value: string, href: string, icon: string, description: string}>
     */
    private function channels(): array
    {
        $channels = [];

        if (! empty($this->config->email)) {
            $channels[] = [
                'label' => 'E-mail',
                'value' => $this->config->email,
                'href' => 'mailto:'.$this->config->email,
                'icon' => 'envelope',
                'description' => 'Resposta em até 1 dia útil — o canal com histórico.',
            ];
        }

        $whatsapp = (string) ($this->config->whatsapp ?: $this->config->cell_phone ?: $this->config->phone);

        if ($whatsapp !== '') {
            $digits = preg_replace('/\D+/', '', $whatsapp) ?? '';

            $channels[] = [
                'label' => 'WhatsApp',
                'value' => $this->formatPhone($digits),
                'href' => 'https://wa.me/'.$this->withCountryCode($digits),
                'icon' => 'whatsapp',
                'description' => 'Para dúvidas rápidas durante o horário comercial.',
            ];
        }

        if (! empty($this->config->telegram)) {
            $handle = ltrim((string) $this->config->telegram, '@');

            $channels[] = [
                'label' => 'Telegram',
                'value' => str_starts_with($handle, 'http') ? $handle : '@'.$handle,
                'href' => str_starts_with($handle, 'http') ? $handle : 'https://t.me/'.$handle,
                'icon' => 'paper-airplane',
                'description' => 'Avisos e suporte por mensagem.',
            ];
        }

        if (! empty($this->config->cell_phone) || ! empty($this->config->phone)) {
            $phone = preg_replace('/\D+/', '', (string) ($this->config->cell_phone ?: $this->config->phone)) ?? '';

            $channels[] = [
                'label' => 'Telefone',
                'value' => $this->formatPhone($phone),
                'href' => 'tel:+'.$this->withCountryCode($phone),
                'icon' => 'lifebuoy',
                'description' => 'Atendimento de segunda a sexta, das 8h às 18h.',
            ];
        }

        return $channels;
    }

    /**
     * @return array<int, array{q: string, a: string}>
     */
    private function faqs(): array
    {
        return [
            [
                'q' => 'Quanto tempo leva para receber uma resposta?',
                'a' => 'Respondemos em até 1 dia útil pelo e-mail informado. Mensagens pelo WhatsApp costumam ser respondidas no mesmo dia, dentro do horário comercial (segunda a sexta, 8h às 18h).',
            ],
            [
                'q' => 'Preciso ser cliente para falar com a equipe?',
                'a' => 'Não. Atendemos também quem ainda está avaliando a plataforma — dúvidas sobre planos, funcionalidades e migração de planilhas são bem-vindas.',
            ],
            [
                'q' => 'Já sou aluno e quero ajuda com o app. Por onde começo?',
                'a' => 'O primeiro contato é com o seu treinador, que enxerga seus treinos e cobranças. Se o problema for técnico (acesso, senha, pagamento online), fale com a gente por esta página informando o e-mail da sua conta.',
            ],
            [
                'q' => 'Como funciona o pagamento pelo aplicativo?',
                'a' => 'A cobrança online é gerada pelo seu treinador e pode ser paga por Pix direto no app; o repasse ao treinador é automático. Cobranças registradas manualmente são combinadas diretamente com ele.',
            ],
            [
                'q' => 'Meus dados estão protegidos?',
                'a' => 'Sim. O tratamento segue a nossa Política de Privacidade (LGPD) e cada treinador acessa apenas os próprios alunos, treinos e cobranças.',
            ],
        ];
    }

    /** Telefone só com dígitos → "(DDD) número" (fixo ou celular). */
    private function formatPhone(string $digits): string
    {
        return match (strlen($digits)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits) ?? $digits,
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits) ?? $digits,
            default => $digits,
        };
    }

    /** Garante o DDI 55 nos links de WhatsApp/telefone. */
    private function withCountryCode(string $digits): string
    {
        if ($digits === '') {
            return '';
        }

        return strlen($digits) >= 12 ? $digits : '55'.$digits;
    }
}
