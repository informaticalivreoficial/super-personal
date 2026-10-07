<?php

namespace App\Livewire\Dashboard\Reports;

use Livewire\Component;
use Analytics;
use Spatie\Analytics\Period;
use Carbon\Carbon;

class DashboardStats extends Component
{
    public $barChartData = [];
    public $donutChartData = [];
    public $topPagesChart = [];

    public function mount()
    {
        try {

            $topPages = Analytics::get(
                Period::months(1),
                metrics: ['screenPageViews'],
                dimensions: ['pageTitle']
            );

            $topPages = collect($topPages)
                ->sortByDesc('screenPageViews')
                ->take(5);

            $this->topPagesChart = [
                'labels' => $topPages
                    ->pluck('pageTitle')
                    ->map(fn($t) => \Illuminate\Support\Str::limit($t, 40)),

                'values' => $topPages
                    ->pluck('screenPageViews')
                    ->map(fn($v) => (int) $v),
            ];

            $analyticsData = cache()->remember('dashboard_6_months', 60, function () {
                return Analytics::get(
                    Period::months(6),
                    metrics: ['totalUsers', 'sessions'],
                    dimensions: ['yearMonth']
                );
            });

            $sorted = collect($analyticsData)->sortBy('yearMonth');

            $labels = [];
            $users = [];
            $sessions = [];

            foreach ($sorted as $row) {

                if (!isset($row['yearMonth'])) continue;

                $date = Carbon::createFromFormat('Ym', $row['yearMonth']);

                $labels[] = $date->translatedFormat('M/Y');
                $users[] = (int) $row['totalUsers'];
                $sessions[] = (int) $row['sessions'];
            }

            $this->barChartData = compact('labels', 'users', 'sessions');

            // 📊 dispositivos (melhor que browser)
            $devices = Analytics::get(
                Period::months(6),
                metrics: ['sessions'],
                dimensions: ['deviceCategory']
            );

            $this->donutChartData = [
                'labels' => collect($devices)->pluck('deviceCategory'),
                'values' => collect($devices)->pluck('sessions'),
            ];

            // 📈 stats
            $totalSessions = array_sum($sessions);
            $totalUsers = array_sum($users);

            $growth = 0;
            if (count($sessions) >= 2) {
                $last = end($sessions);
                $prev = prev($sessions);
                if ($prev > 0) {
                    $growth = (($last - $prev) / $prev) * 100;
                }
            }

            $this->stats = [
                'sessions' => $totalSessions,
                'users' => $totalUsers,
                'growth' => round($growth, 1),
            ];

        } catch (\Exception $e) {

            $this->barChartData = ['labels' => [], 'users' => [], 'sessions' => []];
            $this->donutChartData = ['labels' => [], 'values' => []];
            $this->stats = ['sessions' => 0, 'users' => 0, 'growth' => 0];
            $this->topPagesChart = [
                'labels' => [],
                'values' => [],
            ];
        }
    }
    
    public function render()
    {
        return view('livewire.dashboard.reports.dashboard-stats');
    }
}
