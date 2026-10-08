<?php

use App\Http\Controllers\Web\SiteController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\Exercises\ExerciseForm;
use App\Livewire\Dashboard\Exercises\ExerciseIndex;
use App\Livewire\Dashboard\Payments\PaymentForm;
use App\Livewire\Dashboard\Payments\PaymentIndex;
use App\Livewire\Dashboard\Plans\PlanForm;
use App\Livewire\Dashboard\Plans\PlanIndex;
use App\Livewire\Dashboard\Plans\PlanShow;
use App\Livewire\Dashboard\Posts\CatPosts;
use App\Livewire\Dashboard\Posts\Lixeira;
use App\Livewire\Dashboard\Posts\PostForm;
use App\Livewire\Dashboard\Posts\Posts;
use App\Livewire\Dashboard\Reports\Posts as ReportsPosts;
use App\Livewire\Dashboard\Settings;
use App\Livewire\Dashboard\Sitemap\SitemapGenerator;
use App\Livewire\Dashboard\Sports\SportForm;
use App\Livewire\Dashboard\Sports\SportIndex;
use App\Livewire\Dashboard\Students\StudentForm;
use App\Livewire\Dashboard\Students\StudentIndex;
use App\Livewire\Dashboard\Students\StudentShow;
use App\Livewire\Dashboard\Users\Form;
use App\Livewire\Dashboard\Users\Time;
use App\Livewire\Dashboard\Users\Users;
use App\Livewire\Dashboard\Users\ViewUser;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Web', 'as' => 'web.'], function () {

    /** Página Inicial */
    Route::get('/', [SiteController::class, 'home'])->name('home');

    Route::get('/blog/artigo/{slug}', [SiteController::class, 'artigo'])->name('blog.artigo');
    Route::get('/blog/categoria/{slug}', [SiteController::class, 'categoria'])->name('blog.categoria');
    Route::get('/blog', [SiteController::class, 'artigos'])->name('blog.artigos');

    // //*************************************** Páginas *******************************************/
    Route::get('/noticia/{slug}', [SiteController::class, 'noticia'])->name('noticia');
    Route::get('/noticias', [SiteController::class, 'noticias'])->name('noticias');
    Route::get('/noticias/categoria/{slug}', [SiteController::class, 'categoria'])->name('noticia.categoria');

    Route::get('/pagina/{slug}', [SiteController::class, 'page'])->name('pagina');

});

/*
 * Painel do SaaS (professores + admin da plataforma).
 * Papéis garantidos pelo middleware `role`; tenant isolado por Policies/global scope.
 * Obs.: middleware `verified` removido — e-mail não é verificado no MVP (sem MustVerifyEmail).
 */
Route::group(['middleware' => ['auth', 'role:teacher,admin'], 'prefix' => 'admin'], function () {

    Route::get('/', Dashboard::class)->name('admin');

    // *********************** Alunos **********************************************/
    Route::get('alunos', StudentIndex::class)->name('students.index');
    Route::get('alunos/cadastrar', StudentForm::class)->name('students.create');
    Route::get('alunos/{student}/editar', StudentForm::class)->name('students.edit');
    Route::get('alunos/{student}', StudentShow::class)->name('students.show');

    // *********************** Planos de treino *************************************/
    Route::get('planos', PlanIndex::class)->name('plans.index');
    Route::get('planos/cadastrar', PlanForm::class)->name('plans.create');
    Route::get('planos/{plan}/editar', PlanForm::class)->name('plans.edit');
    Route::get('planos/{plan}', PlanShow::class)->name('plans.show');

    // *********************** Pagamentos *******************************************/
    Route::get('pagamentos', PaymentIndex::class)->name('payments.index');
    Route::get('pagamentos/cadastrar', PaymentForm::class)->name('payments.create');
    Route::get('pagamentos/{payment}/editar', PaymentForm::class)->name('payments.edit');

    // *********************** Biblioteca de exercícios *****************************/
    Route::get('exercicios', ExerciseIndex::class)->name('exercises.index');
    Route::get('exercicios/cadastrar', ExerciseForm::class)->name('exercises.create');
    Route::get('exercicios/{exercise}/editar', ExerciseForm::class)->name('exercises.edit');

    // *********************** Modalidades (catálogo global) ************************
    // Sports não têm tenant: criação/edição/exclusão é exclusiva do admin da
    // plataforma (SportPolicy); o professor apenas consulta no dropdown das sessões.
    Route::group(['middleware' => 'role:admin'], function () {
        Route::get('modalidades', SportIndex::class)->name('sports.index');
        Route::get('modalidades/cadastrar', SportForm::class)->name('sports.create');
        Route::get('modalidades/{sport}/editar', SportForm::class)->name('sports.edit');
    });

    // *********************** Resíduos do starter (remover na Fase 2) *************/
    Route::get('configuracoes', Settings::class)->name('settings');
    Route::get('sitemap-generator', SitemapGenerator::class)->name('sitemap.generator');

    // *********************** Usuários **********************************************/
    Route::get('usuarios/clientes', Users::class)->name('users.index');
    Route::get('usuarios/time', Time::class)->name('users.time');
    Route::get('usuarios/cadastrar', Form::class)->name('users.create');
    Route::get('usuarios/{userId}/editar', Form::class)->name('users.edit');
    Route::get('usuarios/{user}/visualizar', ViewUser::class)->name('users.view');

    // *********************** Posts *********************************************/
    Route::get('posts/{post}/editar', PostForm::class)->name('posts.edit');
    Route::get('posts/cadastrar', PostForm::class)->name('posts.create');
    Route::get('posts/categorias', CatPosts::class)->name('posts.categories.index');
    Route::get('/posts/lixeira', Lixeira::class)->name('posts.lixeira');
    Route::get('posts', Posts::class)->name('posts.index');

    Route::get('posts/reports', ReportsPosts::class)->name('posts.reports');

});

// Authentication routes
Route::group(['prefix' => 'auth'], function () {
    Route::get('login', Login::class)->name('login')->middleware('guest');
    Route::get('register', Register::class)->name('register')->middleware('guest');
});
