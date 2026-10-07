<?php

namespace App\Livewire\Dashboard;

use App\Models\Ad;
use App\Models\AdContract;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Post;
use Livewire\Component;

class Dashboard extends Component
{
    public $topcompanies = [];

    public function render()
    {        
        $noticiasCount = Post::where('type', 'noticia')->count();
        $noticiasYearCount = Post::where('type', 'noticia')->whereYear('created_at', now()->year)->count();

        $articlesCount = Post::where('type', 'artigo')->count();
        $articlesYearCount = Post::where('type', 'artigo')->whereYear('created_at', now()->year)->count();
        
        
        

        $title = 'Painel de Controle';

        return view('livewire.dashboard.dashboard', [
            'title' => $title, 
            'noticiasCount' => $noticiasCount,
            'noticiasYearCount' => $noticiasYearCount,
            'articlesCount' => $articlesCount,
            'articlesYearCount' => $articlesYearCount
        ]);
    }
}
