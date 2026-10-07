<?php

namespace App\Livewire\Dashboard\Reports;

use App\Models\Post;
use Carbon\Carbon;
use Livewire\Component;

class Posts extends Component
{
    public $period = '30'; // dias
    public $type = 'all';

    public array $labels = [];
    public array $data   = [];

    public $totalPosts = 0;
    public $totalArtigos = 0;
    public $totalNoticias = 0;
    public $totalViews = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function updated($field)
    {
        $this->loadData();
    }

    public function updatedPeriod(): void
    {
        $this->loadData();
    }

    public function updatedType(): void
    {
        $this->loadData();
    }

    public function loadData()
    {
        $startDate = now()->subDays((int) $this->period)->startOfDay();
        $endDate   = now()->endOfDay();

        $baseQuery = Post::whereBetween('created_at', [$startDate, $endDate]);

        $query = clone $baseQuery;

        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        $this->totalPosts    = (clone $baseQuery)->count();
        $this->totalArtigos  = (clone $baseQuery)->where('type', 'artigo')->count();
        $this->totalNoticias = (clone $baseQuery)->where('type', 'noticia')->count();
        $this->totalViews    = (clone $baseQuery)->sum('views');

        $posts = $query
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // ✅ converte para array simples
        $this->labels = $posts->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))
            ->values()
            ->all();

        $this->data = $posts->pluck('total')
            ->values()
            ->all();

        $this->dispatch('updateChart', [
            'labels' => $this->labels,
            'data'   => $this->data,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.reports.posts',[
            'title' => 'Relatório de Artigos e Notícias'
        ]);
    }
}
