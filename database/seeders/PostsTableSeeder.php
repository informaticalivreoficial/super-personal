<?php
namespace Database\Seeders;

use App\Models\CatPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PostsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $categories = CatPost::all();

        Post::factory(50)->make()->each(function ($post) use ($users, $categories) {
            $post->autor = $users->random()->id;
            $post->category = $categories->random()->id;
            $post->save();
        });
    }
}
