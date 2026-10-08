<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="link" class="h-6 w-6 text-teal-600" />
                    Sitemap
                </h1>
                <p class="mt-1 text-sm text-gray-500">SEO / Gerador de sitemap</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-sky-50 text-sky-600"><x-icon name="link" class="h-6 w-6" /></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total de URLs</span>
                            <span class="info-box-number">{{ $totalUrls }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-green-50 text-green-600"><x-icon name="clock" class="h-6 w-6" /></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Última Geração</span>
                            <span class="info-box-number">{{ $lastGenerated ?? 'Nunca gerado' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button wire:click="generate" class="btn btn-primary btn-lg"
                    wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4" /> Gerar Sitemap Agora
                    </span>
                    <span wire:loading wire:target="generate" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" /> Gerando...
                    </span>
                </button>

                @if($lastGenerated)
                    <a href="{{ asset('sitemap.xml') }}" target="_blank" class="btn btn-success btn-lg">
                        <x-icon name="eye" class="h-4 w-4" /> Visualizar Sitemap
                    </a>
                @endif
            </div>

            <div class="mt-6">
                <div class="alert alert-info">
                    <h5 class="flex items-center gap-2 font-semibold"><x-icon name="information-circle" class="h-5 w-5" /> Informações</h5>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>O sitemap é salvo em: <code class="rounded bg-sky-100 px-1 py-0.5 text-xs">{{ public_path('sitemap.xml') }}</code></li>
                        <li>Adicione no Google Search Console: <code class="rounded bg-sky-100 px-1 py-0.5 text-xs">{{ url('sitemap.xml') }}</code></li>
                        <li>Configure para gerar automaticamente via cron job</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
