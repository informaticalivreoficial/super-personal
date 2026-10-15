<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\MyProfile;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Página "Meu perfil": edição dos próprios dados da conta (teacher e admin),
 * sincronização com o perfil teachers e senha opcional.
 */
class MyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_page_renders_prefilled_for_teacher(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'Ana Silva',
            'phone' => '11 98888-7777',
        ]);
        // Factory gera nomes divergentes em user/teacher; no fluxo real o
        // TeacherService sincroniza — aqui alinha para testar o prefill.
        $teacher->user->update(['name' => 'Ana Silva', 'email' => 'ana@exemplo.com']);

        $this->actingAs($teacher->user);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Meu perfil')
            ->assertSee('Ana Silva')
            ->assertSee('ana@exemplo.com')
            ->assertSee('11 98888-7777');
    }

    public function test_teacher_saves_name_email_phone_syncing_tenant(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Ana Silva']);

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('name', 'Ana Souza')
            ->set('email', 'ana.souza@exemplo.com')
            ->set('phone', '11 97777-6666')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', [
            'id' => $teacher->user_id,
            'name' => 'Ana Souza',
            'email' => 'ana.souza@exemplo.com',
            'phone' => '11 97777-6666',
        ]);

        // Perfil do tenant sincronizado (landing/cadastro).
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Ana Souza',
            'phone' => '11 97777-6666',
        ]);
    }

    public function test_email_unique_ignores_own_account(): void
    {
        $teacher = Teacher::factory()->create();
        Teacher::factory()->create(); // outro treinador com e-mail próprio

        $this->actingAs($teacher->user);

        // E-mail igual ao próprio: não é conflito.
        Livewire::test(MyProfile::class)
            ->set('name', 'Nome Novo')
            ->set('email', $teacher->user->email)
            ->call('save')
            ->assertHasNoErrors();

        // E-mail de outro usuário: erro.
        $other = Teacher::factory()->create();

        Livewire::test(MyProfile::class)
            ->set('email', $other->user->email)
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_password_is_optional_and_hashed(): void
    {
        $teacher = Teacher::factory()->create();
        $oldHash = $teacher->user->password;

        $this->actingAs($teacher->user);

        // Sem senha: mantém o hash atual.
        Livewire::test(MyProfile::class)
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($oldHash, $teacher->user->fresh()->password);

        // Com senha: grava hash novo.
        Livewire::test(MyProfile::class)
            ->set('password', 'senha-nova-123')
            ->set('password_confirmation', 'senha-nova-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('senha-nova-123', $teacher->user->fresh()->password));
    }

    public function test_password_validation(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('password', 'curta')
            ->set('password_confirmation', 'curta')
            ->call('save')
            ->assertHasErrors(['password']);

        Livewire::test(MyProfile::class)
            ->set('password', 'senha-nova-123')
            ->set('password_confirmation', 'outra-senha')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_required_and_phone_validation(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('name', '')
            ->set('email', 'nao-e-email')
            ->set('phone', 'abc')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'phone']);
    }

    public function test_cpf_is_formatted_on_prefill(): void
    {
        $teacher = Teacher::factory()->create();
        // Mutator limpa no save; o accessor formata o prefill (###.###.###-##).
        $teacher->user->update(['cpf' => '12345678900']);

        $this->actingAs($teacher->user);

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('123.456.789-00');
    }

    public function test_admin_without_teacher_profile_can_edit(): void
    {
        $admin = User::factory(['role' => 'admin'])->create(['name' => 'Admin']);

        $this->actingAs($admin);

        Livewire::test(MyProfile::class)
            ->set('name', 'Admin Silva')
            ->set('email', $admin->email)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Silva',
        ]);
    }

    public function test_sidebar_shows_profile_link(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('admin'))
            ->assertOk()
            ->assertSee(route('profile.show'), false)
            ->assertSee('Meu perfil');
    }

    public function test_teacher_saves_documents_contact_and_address(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('name', 'Ana Silva')
            ->set('email', $teacher->user->email)
            ->set('additionalEmail', 'ana.extra@exemplo.com')
            ->set('birthday', '25/12/1990')
            ->set('cpf', '123.456.789-00')
            ->set('rg', '12.345.678-9')
            ->set('rgExpedition', 'SSP/SP')
            ->set('phone', '11 3333-4444')
            ->set('cellPhone', '(11) 99999-8888')
            ->set('whatsapp', '11 99999-8888')
            ->set('zipcode', '01310-100')
            ->set('street', 'Av. Paulista')
            ->set('number', '1000')
            ->set('complement', 'Sala 12')
            ->set('neighborhood', 'Bela Vista')
            ->set('city', 'São Paulo')
            ->set('state', 'sp')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', [
            'id' => $teacher->user_id,
            'additional_email' => 'ana.extra@exemplo.com',
            // Mutator do User converte d/m/Y → Y-m-d.
            'birthday' => '1990-12-25',
            // Mutator do User limpa o CPF para dígitos.
            'cpf' => '12345678900',
            'rg' => '12.345.678-9',
            'rg_expedition' => 'SSP/SP',
            'cell_phone' => '11999998888',
            // Mutator do User limpa o CEP para dígitos.
            'zipcode' => '01310100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'complement' => 'Sala 12',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            // Service normaliza a UF em maiúsculo.
            'state' => 'SP',
        ]);
    }

    public function test_birthday_format_is_validated(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('birthday', '1990-12-25')
            ->call('save')
            ->assertHasErrors(['birthday']);

        Livewire::test(MyProfile::class)
            ->set('birthday', '25/12/1990')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_state_must_be_two_letters(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('state', 'São')
            ->call('save')
            ->assertHasErrors(['state']);
    }

    public function test_avatar_upload_and_removal(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('avatar', UploadedFile::fake()->image('avatar.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists("users/{$teacher->user_id}/avatar.png");
        $this->assertSame(
            "users/{$teacher->user_id}/avatar.png",
            $teacher->user->refresh()->avatar
        );

        // Remoção: caminho some do banco e o arquivo é apagado.
        Livewire::test(MyProfile::class)
            ->set('removeAvatar', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($teacher->user->refresh()->avatar);
        Storage::disk('public')->assertMissing("users/{$teacher->user_id}/avatar.png");
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('avatar', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['avatar']);
    }
}
