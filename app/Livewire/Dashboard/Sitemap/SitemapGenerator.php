<?php

namespace App\Livewire\Dashboard\Sitemap;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;

class SitemapGenerator extends Component
{
    public $totalUrls = 0;
    public $lastGenerated = null;

    public function mount()
    {
        $this->loadInfo();
    }

    public function loadInfo()
    {
        if (file_exists(public_path('sitemap.xml'))) {
            $this->lastGenerated = date('d/m/Y H:i:s', filemtime(public_path('sitemap.xml')));
            
            // Conta URLs no sitemap
            $xml = simplexml_load_file(public_path('sitemap.xml'));
            $this->totalUrls = count($xml->url);
        }
    }

    public function generate()
    {
        try {
            Artisan::call('sitemap:generate');
            
            $this->loadInfo();
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Sitemap gerado com sucesso!'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Erro ao gerar sitemap: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.dashboard.sitemap.sitemap-generator');
    }
}
