<?php

namespace App\Livewire\Dashboard\Users;

use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Form extends Component
{
    use WithFileUploads;

    public User $user;

    public $userId;  
      

    public $foto; // Propriedade para armazenar a foto temporariamente
    public $fotoUrl; // Propriedade para armazenar o caminho da foto após o upload

    public $roles;
    public array $roleLabels = [
        'super-admin' => 'Super Administrador',
        'admin'       => 'Administrador',
        'manager'     => 'Gerente',
        'employee'    => 'Colaborador',
    ];
    public $roleSelected = '';
    
    protected function rulesCreate()
    {
        $rules = [
            'name' => 'required|min:3',
            'gender' => 'required|in:masculino,feminino',
            'civil_status' => 'required|in:casado,separado,solteiro,divorciado,viuvo',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|cpf|unique:users,cpf',
            'cell_phone' => 'required',
            'information' => 'nullable|string|max:2000',
            'birthday' => 'required|date_format:d/m/Y|before:today',
            //'foto' => 'nullable|image|max:2048',

            'roleSelected' => 'required|in:employee,manager,admin,super-admin',

            'code' => $this->roleSelected !== 'employee'
                ? 'required|min:6|confirmed'
                : 'nullable',
        ];

        return $rules;
    }

    protected function rulesUpdate()
    {
        return [
            'name' => 'required|min:3|max:191',
            'gender' => 'required|in:masculino,feminino',
            'civil_status' => 'required|in:casado,separado,solteiro,divorciado,viuvo',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'cpf' => 'required|cpf|unique:users,cpf,' . $this->userId,
            'cell_phone' => 'required',
            'birthday' => 'required|date_format:d/m/Y',
            'information' => 'nullable|string|max:2000',
            //'foto' => 'nullable|image|max:2048',

            // 'code' => $this->roleSelected !== 'employee'
            //     ? 'required|min:6|confirmed'
            //     : 'nullable|min:6|confirmed',

            // 'code_confirmation' => 'same:code',
        ];
    }    
    
    public $gender; 

    //Informations about
    public $name, $cargo, $birthday, $naturalness, $civil_status, $avatar, $information;    
    
    //Documents
    public $cpf, $rg, $rg_expedition;

    //Address
    public $zipcode = '', $street, $neighborhood, $city, $state, $complement, $number;

    //Contact
    public $phone, $cell_phone, $whatsapp, $email, $additional_email, $telegram;

    //Social
    public $facebook, $instagram, $linkedin;

    public $code;
    public $code_confirmation;

    public $errorMessage;

    //protected $listeners = ['atualizar-data' => 'atualizarData'];
    
    //$this->userId = null ? 'Novo Cliente' : 'Editar Cliente'

    public function mount($userId = null)
    {
        if ($userId) {
            $user = User::findOrFail($userId);
            $this->authorize('update', $user);
            $this->userId = $user->id;
            $this->fill($user->toArray());
            $this->roleSelected = $user->roles->pluck('name')->first() ?? '';
        }
    }

    

    public function save()
    {
        $this->userId ? $this->update() : $this->create();
    }

    public function create(): void
    {
        try {
            $validated = $this->validate($this->rulesCreate());

            if ($this->foto) {
                $validated['avatar'] = $this->foto->store('user', 'public');
            }

            $validated['password'] = $this->roleSelected !== 'employee'
                ? Hash::make($this->code)
                : Hash::make(Str::random(12));

            

            $extras = [
                'cargo', 'naturalness', 'rg', 'rg_expedition',
                'phone', 'whatsapp', 'additional_email', 'telegram',
                'number', 'zipcode', 'street', 'neighborhood',
                'city', 'state', 'complement',
                'facebook', 'instagram', 'linkedin', 'information',
            ];

            foreach ($extras as $field) {
                $validated[$field] = $this->$field;
            }

            $user = User::create($validated);
            $user->syncRoles([$this->roleSelected]);

            $this->reset(['code', 'code_confirmation', 'foto']);
            $this->dispatch('user-cadastrado');

            redirect()->route('users.edit', $user->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        }
    }

    public function update()
    {    
        try {
            
            $validated = $this->validate($this->rulesUpdate());
        
            $user = User::findOrFail($this->userId);

            //$this->authorize('update', $user);

            if ($this->foto) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $validated['avatar'] = $this->foto->store('user', 'public');
            }            

            $data = array_merge($validated, [
                'cargo'            => $this->cargo,
                'naturalness'      => $this->naturalness,
                'rg'               => $this->rg,
                'rg_expedition'    => $this->rg_expedition,
                'phone'            => $this->phone,
                'whatsapp'         => $this->whatsapp,
                'additional_email' => $this->additional_email,
                'telegram'         => $this->telegram,
                'number'           => $this->number,
                'zipcode'          => $this->zipcode,
                'street'           => $this->street,
                'neighborhood'     => $this->neighborhood,
                'city'             => $this->city,
                'state'            => $this->state,
                'complement'       => $this->complement,
                'facebook'         => $this->facebook,
                'instagram'        => $this->instagram,
                'linkedin'         => $this->linkedin,
                'information'      => $this->information,
            ]);
            
            $user->update($data);
            $user->syncRoles([$this->roleSelected]);

            $this->reset(['code', 'code_confirmation', 'foto']);
            $this->dispatch('user-atualizado');
        } catch (\Illuminate\Validation\ValidationException $e) {
            
            $this->dispatch('toast', 
                type: 'error', 
                message: $e->validator->errors()->first()
            );
            throw $e;
        }    
    }    

    public function updatedZipcode(string $value)
    {        
        $this->zipcode = preg_replace('/[^0-9]/', '', $value);

        if(strlen($this->zipcode) === 8){
            $response = Http::get("https://viacep.com.br/ws/{$this->zipcode}/json/")->json();            
            if(!isset($response['erro'])){                
                $this->street = $response['logradouro'] ?? '';
                $this->neighborhood = $response['bairro'] ?? '';
                $this->state = $response['uf'] ?? '';
                $this->city = $response['localidade'] ?? '';
                $this->complement = $response['complemento'] ?? '';      
            }else{                
                $this->addError('zipcode', 'CEP não encontrado.'); 
            }
        }
    }

    public function updatedFoto(): void
    {
        $this->validateOnly('foto', [
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $this->fotoUrl = $this->foto->temporaryUrl();
    }

    public function updatedRoleSelected($value): void
    {
        if ($value === 'employee') {
            $this->code = null;
            $this->code_confirmation = null;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.users.form');
    }

}
