<?php

namespace Database\Seeders;

use App\Models\CatPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CatPostsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Categorias principais
        $categorias = CatPost::factory(5)->create();

        // Subcategorias
        foreach ($categorias as $cat) {
            CatPost::factory(3)->create([
                'id_pai' => $cat->id
            ]);
        }
    }
}
