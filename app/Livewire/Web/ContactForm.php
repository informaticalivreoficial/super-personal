<?php

namespace App\Livewire\Web;

use App\Mail\Web\OrcamentoRetorno;
use App\Mail\Web\SendOrcamento;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $message = '';
    public string $whatsapp = '';
    public string $cidade = '';

    public bool $enviado = false;

    public function render()
    {
        return view('livewire.web.contact-form');
    }

    public function submit()
    {
        if (!empty($this->cidade)) {
            abort(403, 'Spam detectado');
        }

        $validated = $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
            'message' => 'required|min:10',
        ]);        

        $data = [
            'sitename' => config('app.name'),
            'siteemail' => config('mail.from.address'),
            'reply_name' => $validated['name'],
            'reply_email' => $validated['email'],
            'reply_whatsapp' => $this->whatsapp,
            'whatsapp' => $this->whatsapp,
            'message' => $validated['message'],       
        ];

        Mail::send(new SendOrcamento($data));
        Mail::send(new OrcamentoRetorno($data));
        $this->reset(['name', 'email', 'whatsapp', 'message']);
        $this->enviado = true;
        session()->flash('success', 'Mensagem enviada com sucesso!');        
    }
}
