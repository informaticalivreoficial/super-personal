<?php
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ConfigTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('config')->insert([
            'id' => 1,
            'email' => 'teste@teste.com.br',
            'app_name' => 'Nome da Aplicação',
            'social_name' => 'Razão Social',
            'alias_name' => 'Nome da Empresa',
            'information' => 'Descrição',
            'zipcode' => '11680000',
            'city' => 'Ubatuba',
            'state' => 'SP',
            'rss' => 'teste',
            'sitemap' => 'teste',
            'rss_data' => now(),
            'sitemap_data' => now(),
            'template' => 'default',
            'phone' => '(11) 1111-1111',
            'cell_phone' => '(11) 11111-1111',
            'whatsapp' => '(11) 11111-1111'            
        ]);  
    }
}
