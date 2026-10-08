<div>
    @section('title', $title)
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="chart-bar" class="h-6 w-6 text-teal-600" />
                    Relatórios de Posts
                </h1>
                <p class="mt-1 text-sm text-gray-500">Posts / Relatórios</p>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
