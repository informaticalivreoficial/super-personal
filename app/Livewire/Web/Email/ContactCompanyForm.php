<?php

namespace App\Livewire\Web\Email;

use App\Mail\Web\ParceiroSend;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ContactCompanyForm extends Component
{
    public Company $empresa;

    public $nome;
    public $email;
    public $mensagem;
    public $success = false;

    protected function rules()
    {
        return [
            'nome' => 'required|string|min:3',
            'email' => 'required|email:rfc,dns',
            'mensagem' => 'required|min:10',
        ];
    }

    public function send()
    {
        $data = $this->validate();

        $key = 'contact-'.md5($data['email']);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('email', 'Muitas tentativas. Tente novamente depois.');
            return;
        }

        RateLimiter::hit($key, 60);

        $payload = [
            'reply_name' => $data['nome'],
            'reply_email' => $data['email'],
            'mensagem' => $data['mensagem'],

            'empresaemail' => $this->empresa->email,
            'empresaname' => $this->empresa->alias_name,

            'siteemail' => config('mail.from.address'),
            'sitename' => config('app.name'),

            'config_site_name' => config('app.name'),
        ];

        Mail::send(new ParceiroSend($payload));

        $this->reset(['nome', 'email', 'mensagem']);

        $this->success = true;
    }

    public function render()
    {
        return view('livewire.web.email.contact-company-form');
    }
}
