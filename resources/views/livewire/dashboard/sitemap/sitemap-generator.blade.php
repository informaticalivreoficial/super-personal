<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-sitemap mr-2"></i> Sitemap</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-link"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total de URLs</span>
                            <span class="info-box-number">{{ $totalUrls }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Última Geração</span>
                            <span class="info-box-number">{{ $lastGenerated ?? 'Nunca gerado' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <button wire:click="generate" class="btn btn-primary btn-lg">
                        <i class="fas fa-sync-alt mr-2"></i> Gerar Sitemap Agora
                    </button>

                    @if($lastGenerated)
                        <a href="{{ asset('sitemap.xml') }}" target="_blank" class="btn btn-success btn-lg ml-2">
                            <i class="fas fa-eye mr-2"></i> Visualizar Sitemap
                        </a>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Informações</h5>
                        <ul>
                            <li>O sitemap é salvo em: <code class="text-lime-200">{{ public_path('sitemap.xml') }}</code></li>
                            <li>Adicione no Google Search Console: <code class="text-lime-200">{{ url('sitemap.xml') }}</code></li>
                            <li>Configure para gerar automaticamente via cron job</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts') 
    <script>
        document.addEventListener('livewire:init', () => {
            // Configurações do Toastr
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "4000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
                "preventDuplicates": false,
                "newestOnTop": true
            };

            // Listener para o evento toast
            Livewire.on('toast', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                toastr[data.type](data.message);
            });
        });
    </script>
@endpush