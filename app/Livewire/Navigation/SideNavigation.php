<?php

namespace App\Livewire\Navigation;

use App\Models\Company;
use App\Models\Config;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Livewire\Component;

class SideNavigation extends Component
{
    public function render()
    {
        //$clientCount = User::where('client', 1)->count();
        // $timeCount = User::where(function($query) {
        //     $query->where('editor', 1)
        //         ->orWhere('admin', 1)
        //         ->orWhere('superadmin', 1);
        // })->count();
        // $postsCount = Post::count();
        //$propertyCount = Property::count();
        // Manifest count
        //$manifestCount = Manifest::where(function($query) {
        //    $query->where('section', 'conferencia')
        //        ->orWhereNull('section');
        //})->count();
        //$manifestComercialCount = Manifest::where('section', 'comercial')->count();
        //$manifestFinanceCount = Manifest::where('section', 'financeiro')->count();
        //$manifestFinishCount = Manifest::where([
        //    ['status', '=', 'entregue'],
        //    ['section', '=', 'finalizado'],
        //])->count();        

        return view('livewire.navigation.side-navigation',[
           
        ]);
    }
}
