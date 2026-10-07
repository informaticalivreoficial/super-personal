<?php

namespace App\Livewire\Dashboard\Users;

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $name;
    public $nasc;
    public $email;
    public $cell_phone;
    public $whatsapp;
    public $additional_email;

    public $password;

    /**@var TemporaryUploadedFile|mixed $image
     */
    // #[Rule('required|max:1024', message: 'Image obrigatória ou o tamanho é maior que 1024MB.')]
    // public $image;
    
    // protected $rules = [
    //     'name' => 'required|string|max:255',
    //     'email' => 'required|email|unique:users,email',
    //     'password' => 'required|string|min:8|confirmed',
    // ];

    // // Mensagens de erro
    // protected $messages = [
    //     'name.required' => 'O nome é obrigatório.',
    //     'email.required' => 'O e-mail é obrigatório.',
    //     'password.required' => 'A senha é obrigatória.',
    //     'password.confirmed' => 'As senhas não coincidem.',
    // ];

    #[Title('Cadastrar Cliente')]
    public function render()
    {
        return view('livewire.dashboard.users.create');
    }

    public function save()
    {
        // $this->validate();

        // $user = User::create([
        //     'name'      => $this->name,
        //     'email'     => $this->email,
        //     'password' => Hash::make('$this->password'),
        // ]);

        $this->dispatch('toastr', [
            'type' => 'success',
            'message' => 'Usuário registrado com sucesso!'
        ]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->dispatch('userId');
    }
}
