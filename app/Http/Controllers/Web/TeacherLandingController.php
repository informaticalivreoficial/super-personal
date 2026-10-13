<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\Teacher;
use App\Models\TrainingSession;
use App\Support\Seo;
use Illuminate\Support\Str;

/**
 * Landing page pública do treinador: /treinador/{slug}.
 *
 * Só renderiza perfis com public_profile + active (opt-in). Exibe apenas
 * conteúdo do próprio tenant (nome, bio, modalidades) — nunca alunos,
 * treinos ou pagamentos. HTML server-renderizado (sem Livewire) p/ SEO.
 */
class TeacherLandingController extends Controller
{
    public function __invoke(string $slug)
    {
        $teacher = Teacher::query()
            ->where('slug', $slug)
            ->where('public_profile', true)
            ->where('active', true)
            ->firstOrFail();

        // Contagem de acessos da landing (coluna teachers.views — métrica
        // exibida no painel e base para análise futura). A visita do próprio
        // dono (logado) não conta, para não inflar o número ao dar preview.
        if (auth()->id() !== $teacher->user_id) {
            $teacher->increment('views');
        }

        // Modalidades distintas usadas nas sessões do próprio tenant.
        // withoutGlobalScope: o visitante pode estar logado como outro
        // treinador — o conteúdo é público e pertence ao dono da página.
        $sports = Sport::query()
            ->whereIn('id', TrainingSession::withoutGlobalScope('tenant')
                ->where('teacher_id', $teacher->id)
                ->select('sport_id'))
            ->orderBy('name')
            ->get();

        $description = $teacher->tagline
            ?: Str::limit(strip_tags($teacher->bio ?? ''), 160, '…');

        if ($description === '') {
            $description = 'Treinos personalizados com '.$teacher->name.' no '.config('app.name').'.';
        }

        // Título da página: o treinador pode exibir o nome da empresa/marca
        // (landing_title) em vez do próprio nome.
        $publicTitle = ($teacher->landing_title ?? '') !== ''
            ? $teacher->landing_title
            : $teacher->name;

        $head = (new Seo)->render(
            $publicTitle.(($teacher->tagline ?? '') !== '' ? ' — '.$teacher->tagline : ''),
            $description,
            route('web.treinador', $teacher->slug),
            $teacher->metaimg_url ?? url('images/image.jpg'),
        );

        return view('web.treinador', [
            'head' => $head,
            'teacher' => $teacher,
            'sports' => $sports,
            'whatsappUrl' => $this->whatsappUrl($teacher),
            'socials' => $this->socials($teacher),
            'addressLines' => $this->addressLines($teacher),
        ]);
    }

    /**
     * Redes sociais preenchidas (rótulo => URL) para a seção "Onde me encontrar".
     *
     * @return array<string, string>
     */
    private function socials(Teacher $teacher): array
    {
        return array_filter([
            'Facebook' => $teacher->facebook,
            'Instagram' => $teacher->instagram,
            'Twitter / X' => $teacher->twitter,
            'YouTube' => $teacher->youtube,
            'LinkedIn' => $teacher->linkedin,
        ]);
    }

    /**
     * Endereço formatado em linhas — só se o treinador optou por exibir
     * (display_address) e há algo preenchido. Privacidade por opt-in.
     *
     * @return array<int, string>
     */
    private function addressLines(Teacher $teacher): array
    {
        if (! $teacher->display_address) {
            return [];
        }

        $firstLine = trim(
            collect([$teacher->street, $teacher->number])->filter()->implode(', ')
            .($teacher->complement !== null && $teacher->complement !== '' ? ' — '.$teacher->complement : '')
        );

        $state = $teacher->state !== null && $teacher->state !== ''
            ? strtoupper($teacher->state)
            : null;

        $cityLine = trim(
            collect([$teacher->city, $state])->filter()->implode(' - ')
        );

        return array_values(array_filter([
            $firstLine ?: null,
            $teacher->neighborhood ?: null,
            $cityLine ?: null,
            $teacher->zipcode ? 'CEP '.$teacher->zipcode : null,
        ]));
    }

    /**
     * Link wa.me do CTA "Falar no WhatsApp".
     * Sem DDI informado, assume Brasil (+55) — SaaS pt-BR.
     */
    private function whatsappUrl(Teacher $teacher): ?string
    {
        if (empty($teacher->whatsapp)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $teacher->whatsapp);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        $text = rawurlencode(
            'Olá, '.$teacher->name.'! Vim pela sua página no '.config('app.name').'.'
        );

        return 'https://wa.me/'.$digits.'?text='.$text;
    }
}
