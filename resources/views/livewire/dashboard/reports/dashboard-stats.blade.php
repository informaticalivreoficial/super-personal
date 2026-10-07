<div>
    <div class="row mb-3">

        <div class="col">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ array_sum($barChartData['sessions']) }}</h3>
                    <p>Visitas</p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ array_sum($barChartData['users']) }}</h3>
                    <p>Usuários</p>
                </div>
            </div>
        </div>

    </div>
    <div class="row">
        <section class="col-lg-6 connectedSortable">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Visitas/Últimos 6 meses</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </section>
        <section class="col-lg-6 connectedSortable">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Dispositivos/Últimos 6 meses</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </section>
        <section class="col-lg-6 connectedSortable">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Páginas Mais Acessadas</h3>
                </div>
                <div class="card-body">
                    <canvas id="pagesChart" style="min-height: 250px;"></canvas>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>   

document.addEventListener('livewire:navigated', () => {

    // ------------------- LINE CHART ---------------------
    const bar = document.getElementById('barChart');

    if (bar) {
        new Chart(bar, {
            type: 'line',
            data: {
                labels: @json($barChartData['labels']),
                datasets: [
                    {
                        label: "Visitas",
                        data: @json($barChartData['sessions']),
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: "Usuários",
                        data: @json($barChartData['users']),
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // ------------------- DONUT ---------------------
    const donut = document.getElementById('donutChart');

    if (donut) {
        const rawValues = @json($donutChartData['values']).map(v => Number(v));
        const total = rawValues.reduce((a, b) => a + b, 0);

        new Chart(donut, {
            type: 'doughnut',
            data: {
                labels: @json($donutChartData['labels']),
                datasets: [{ data: rawValues }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                let percent = total ? ((value / total) * 100).toFixed(1) : 0;

                                return context.label + ': ' +
                                    new Intl.NumberFormat('pt-BR').format(value) +
                                    ' (' + percent + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ------------------- TOP PAGES ---------------------
    const pages = document.getElementById('pagesChart');

    if (pages) {
        new Chart(pages, {
            type: 'bar',
            data: {
                labels: @json($topPagesChart['labels']),
                datasets: [{
                    label: 'Visualizações',
                    data: @json($topPagesChart['values']),
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

});

</script>
@endpush