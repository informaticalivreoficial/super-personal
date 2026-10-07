<div>
    @section('title', $title)
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-chart-bar mr-2"></i> Relatórios de Posts</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">                    
                        <li class="breadcrumb-item"><a href="{{route('admin')}}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Relatórios de posts</li>
                    </ol>
                </div>
            </div>
        </div>    
    </div>

    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

                <div class="bg-white p-4 rounded-xl shadow text-center hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Total Posts</p>
                    <h2 class="text-xl font-bold">{{ $totalPosts }}</h2>
                </div>

                <div class="bg-white p-4 rounded-xl shadow text-center hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Artigos</p>
                    <h2 class="text-xl font-bold text-blue-600">{{ $totalArtigos }}</h2>
                </div>

                <div class="bg-white p-4 rounded-xl shadow text-center hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Notícias</p>
                    <h2 class="text-xl font-bold text-green-600">{{ $totalNoticias }}</h2>
                </div>

                <div class="bg-white p-4 rounded-xl shadow text-center hover:shadow-md transition">
                    <p class="text-sm text-gray-500">Views</p>
                    <h2 class="text-xl font-bold text-purple-600">
                        {{ number_format($totalViews, 0, ',', '.') }}
                    </h2>
                </div>

            </div>
            <div class="flex flex-wrap gap-3 mb-4">
                <select wire:model.live="period"
                    class="border rounded-xl px-3 py-2 bg-white shadow-sm">
                    <option value="7">7 dias</option>
                    <option value="30">30 dias</option>
                    <option value="90">90 dias</option>
                </select>

                <select wire:model.live="type"
                    class="border rounded-xl px-3 py-2 bg-white shadow-sm">
                    <option value="all">Todos</option>
                    <option value="artigo">Artigos</option>
                    <option value="noticia">Notícias</option>
                </select>
            </div>

            <!-- GRÁFICO -->
            <div class="bg-white rounded-2xl shadow p-6" wire:ignore>
                <canvas id="postsReportChart"></canvas>
            </div>
        </div>
    </div>
    
</div>

@push('scripts')
<script>
document.addEventListener('livewire:init', function () {
    let chart;

    function initChart(labels, data) {
        const ctx = document.getElementById('postsReportChart');

        if (!ctx) return;

        if (chart) {
            chart.destroy();
            chart = null;
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Posts publicados',
                    data: data,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    initChart(
        @json($labels),
        @json($data)
    );

    Livewire.on('updateChart', (event) => {
        const payload = event[0];
        initChart(payload.labels, payload.data);
    });
});
</script>
@endpush
