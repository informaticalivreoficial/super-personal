<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\MyProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Primeiro acesso do treinador: enquanto CPF/celular não estiverem preenchidos
 * o painel fica bloqueado (middleware `profile.complete`) e o treinador é
 * levado para `/admin/completar-cadastro`.
 *
 * Motivo de negócio: o parceiro de pagamentos recusa a cobrança da assinatura
 * sem a identificação do pagador (`customer.tax_id` = CPF) — era o 400
 * "must not be null" no Pix. O celular é o contato do tenant.
 */
class ProfileCompletionGateTest extends TestCase
{
    use RefreshDatabase;

    /** Treinador sem os dados obrigatórios (o factory preenche cpf/celular). */
    private function incompleteTeacher(): Teacher
    {
        $teacher = Teacher::factory()->create();
        $teacher->user->update(['cpf' => null, 'cell_phone' => null]);

        return $teacher;
    }

    public function test_teacher_without_required_data_is_redirected_to_completion(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        $this->get(route('admin'))->assertRedirect(route('profile.complete'));
    }

    public function test_every_panel_page_redirects_while_profile_is_incomplete(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        foreach (['admin', 'students.index', 'plans.index', 'payments.index', 'profile.show', 'subscription.show'] as $route) {
            $this->get(route($route))->assertRedirect(route('profile.complete'));
        }
    }

    public function test_completion_page_renders_with_banner_and_missing_fields(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        $this->get(route('profile.complete'))
            ->assertOk()
            ->assertSee('Complete seu cadastro')
            ->assertSee('Só falta completar seus dados para usar o sistema')
            ->assertSee('Faltando agora:')
            ->assertSee('CPF')
            ->assertSee('celular')
            ->assertSee('Salvar e começar a usar');
    }

    public function test_only_cell_phone_missing_also_blocks_and_is_listed_alone(): void
    {
        $teacher = Teacher::factory()->create();
        $teacher->user->update(['cpf' => '12345678900', 'cell_phone' => null]);

        $this->actingAs($teacher->user);

        $this->get(route('admin'))->assertRedirect(route('profile.complete'));

        $this->get(route('profile.complete'))
            ->assertOk()
            ->assertSee('Faltando agora:')
            // Só o celular aparece na lista (o CPF já está preenchido).
            ->assertSeeInOrder(['Faltando agora:', '<strong>celular</strong>'], false)
            ->assertDontSee('<strong>CPF</strong>', false);
    }

    public function test_teacher_with_complete_profile_uses_the_panel(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('admin'))->assertOk();
        $this->get(route('profile.show'))->assertOk();
    }

    public function test_admin_is_not_affected_by_the_gate(): void
    {
        // Admin da plataforma não tem tenant nem assinatura própria.
        $admin = User::factory(['role' => 'admin'])->create(['cpf' => null, 'cell_phone' => null]);

        $this->actingAs($admin);

        $this->get(route('admin'))->assertOk();
    }

    public function test_saving_completion_form_sends_teacher_to_the_panel(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class, ['completing' => true])
            ->set('cpf', '123.456.789-00')
            ->set('cellPhone', '(11) 99999-8888')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin'));

        // Gravado só com dígitos (mutator do User) → vira `tax_id` do gateway.
        $this->assertDatabaseHas('users', [
            'id' => $teacher->user_id,
            'cpf' => '12345678900',
            'cell_phone' => '11999998888',
        ]);

        // Liberado: o middleware não redireciona mais.
        $this->get(route('admin'))->assertOk();
    }

    public function test_completion_requires_cpf_and_cell_phone(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class, ['completing' => true])
            ->set('cpf', '')
            ->set('cellPhone', '')
            ->call('save')
            ->assertHasErrors(['cpf', 'cell_phone'])
            ->assertSee('Informe o CPF (obrigatório para usar o sistema).')
            ->assertSee('Informe um celular (obrigatório para usar o sistema).');

        // Nada foi gravado e o painel continua bloqueado.
        $this->assertNull($teacher->user->refresh()->cpf);
        $this->get(route('admin'))->assertRedirect(route('profile.complete'));
    }

    public function test_completion_rejects_invalid_cpf(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class, ['completing' => true])
            ->set('cpf', '123.456.789')
            ->set('cellPhone', '(11) 99999-8888')
            ->call('save')
            ->assertHasErrors(['cpf'])
            ->assertSee('O CPF deve conter 11 dígitos.');
    }

    public function test_required_markers_are_shown_for_teacher(): void
    {
        $teacher = $this->incompleteTeacher();

        $this->actingAs($teacher->user);

        // CPF e celular vêm marcados com asterisco (obrigatórios) nas duas telas.
        // (Blade injeta comentários de bloco entre o texto e o <span> — comparar por ordem.)
        $this->get(route('profile.complete'))
            ->assertOk()
            ->assertSeeInOrder(['for="profile_cpf">CPF', '<span class="text-red-600">*</span>'], false)
            ->assertSeeInOrder(['for="profile_cell_phone">Celular', '<span class="text-red-600">*</span>'], false);

        $teacher->user->update(['cpf' => '12345678900', 'cell_phone' => '11999998888']);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSeeInOrder(['for="profile_cpf">CPF', '<span class="text-red-600">*</span>'], false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.complete'))->assertRedirect(route('login'));
    }
}
