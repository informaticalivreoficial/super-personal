<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-user mr-2"></i> {{ $userId ? 'Editar' : 'Cadastrar' }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin') }}">Painel de Controle</a></li>
                        @if (auth()->user()->isEmployee())
                            <li class="breadcrumb-item">Colaboradores</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Colaboradores</a></li>
                        @endif
                        
                        <li class="breadcrumb-item active">{{ $userId ? 'Editar' : 'Cadastrar' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" autocomplete="off">
        <div class="card card-primary card-outline">            
            <div class="card-body"> 
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="form-group">
                            <input type="file" id="foto" wire:model="foto" style="display: none;">
                            @error('foto')
                                <span class="error">{{ $message }}</span>
                            @enderror
                            @php
                                if (!empty($avatar) && Storage::exists($avatar)) {
                                    $cover = Storage::url($avatar);
                                } else {
                                    $cover = asset('theme/images/image.jpg');
                                }
                            @endphp
                            @if ($fotoUrl)
                                <label for="foto" class="photo-wrapper">
                                    <img class="photo-preview" src="{{ $fotoUrl }}"
                                        alt="{{ $name }}">
                                </label>
                            @else
                                <label for="foto" class="photo-wrapper">
                                    <img class="photo-preview" src="{{ $cover }}"
                                        alt="{{ $name }}">
                                </label>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-9">
                        <div class="row mb-2 text-muted pl-2">
                            <div class="col-12 col-md-6 col-lg-8 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>*Nome</b></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Nome" wire:model="name">
                                    @error('name')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group" x-data="{ value: @entangle('birthday').defer }" x-init="initFlatpickr()" x-ref="datepicker">
                                    <label class="labelforms"><b>*Data de Nascimento</b></label>
                                    <input type="text" class="form-control @error('birthday') is-invalid @enderror" wire:model="birthday" id="datepicker" />
                                    @error('birthday')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror                                                                                                                                      
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>Genero</b></label>
                                    <select class="form-control @error('gender') is-invalid @enderror" wire:model="gender">
                                        <option value="">Selecione</option>
                                        <option value="masculino">Masculino</option>
                                        <option value="feminino">Feminino</option>
                                    </select>
                                    @error('gender')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>Estado Civil</b></label>
                                    <select class="form-control @error('civil_status') is-invalid @enderror" wire:model="civil_status">                                        
                                        <option value="">Selecione</option>
                                        <option value="casado">Casado</option>
                                        <option value="separado">Separado</option>
                                        <option value="solteiro">Solteiro</option>
                                        <option value="divorciado">Divorciado</option>
                                        <option value="viuvo">Viúvo(a)</option>                                       
                                    </select>
                                    @error('civil_status')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>*CPF</b></label>
                                    <input type="text" class="form-control @error('cpf') is-invalid @enderror" placeholder="000.000.000-00" id="cpf" wire:model="cpf" x-mask="999.999.999-99" />
                                    @error('cpf')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>                                        
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>RG</b></label>
                                    <input type="text" class="form-control" placeholder="RG"
                                        id="rg" wire:model="rg" x-mask="99.999.999-9" />
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>Órgão Expedidor</b></label>
                                    <input type="text" class="form-control" placeholder="Expedição"
                                        id="rg_expedition" wire:model="rg_expedition">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-group">
                                    <label class="labelforms"><b>Naturalidade</b></label>
                                    <input type="text" class="form-control"
                                        placeholder="Cidade de Nascimento" id="naturalness"
                                        wire:model="naturalness">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        
                <div class="card text-muted">
                    <div class="card-header">
                        <h4>
                            <strong>Contato</strong>
                        </h4>
                    </div>                                
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>Telefone fixo:</b></label>
                                    <input type="text" class="form-control" placeholder="(00) 0000-0000"
                                        x-mask="(99) 9999-9999" wire:model="phone" id="phone">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>*Celular:</b></label>
                                    <input type="text" class="form-control @error('cell_phone') is-invalid @enderror" placeholder="(00) 00000-0000"
                                        x-mask="(99) 99999-9999" wire:model="cell_phone"
                                        id="cell_phone">
                                    @error('cell_phone')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>WhatsApp:</b></label>
                                    <input type="text" class="form-control" placeholder="(00) 00000-0000"
                                        x-mask="(99) 99999-9999" wire:model="whatsapp"
                                        id="whatsapp">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>*E-mail:</b></label>
                                    <input type="text" class="form-control @error('email') is-invalid @enderror" placeholder="Email" wire:model="email" id="email">
                                    @error('email')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>E-mail Alternativo:</b></label>
                                    <input type="text" class="form-control"
                                        placeholder="Email Alternativo" wire:model="additional_email"
                                        id="additional_email">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms"><b>Telegram:</b></label>
                                    <input type="text" class="form-control" placeholder="Telegram"
                                        wire:model="telegram" id="telegram">
                                </div>
                            </div>
                        </div>
                    </div>                                
                </div>

                <div class="card text-muted">
                    <div class="card-header">
                        <h4>
                            <strong>Endereço</strong>
                        </h4>
                    </div>                                
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-12 col-md-6 col-lg-2"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>*CEP:</b></label>
                                    <input type="text" x-mask="99.999-999" class="form-control @error('zipcode') is-invalid @enderror" id="zipcode" wire:model.lazy="zipcode">
                                    @error('zipcode')
                                        <span class="error erro-feedback">{{ $message }}</span>
                                    @enderror                                                    
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-4 col-lg-3"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>*Estado:</b></label>
                                    <input type="text" class="form-control" id="state" wire:model="state" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 col-lg-4"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>*Cidade:</b></label>
                                    <input type="text" class="form-control" id="city" wire:model="city" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>*Rua:</b></label>
                                    <input type="text" class="form-control" id="street" wire:model="street" readonly>
                                </div>
                            </div>                                            
                        </div>
                        <div class="row mb-2">
                            <div class="col-12 col-md-4 col-lg-3"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>*Bairro:</b></label>
                                    <input type="text" class="form-control" id="neighborhood" wire:model="neighborhood" readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-2"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>Número:</b></label>
                                    <input type="text" class="form-control" placeholder="Número do Endereço" id="number" wire:model="number">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3"> 
                                <div class="form-group">
                                    <label class="labelforms"><b>Complemento:</b></label>
                                    <input type="text" class="form-control" id="complement" wire:model="complement">
                                </div>
                            </div>   
                        </div>
                    </div>                                
                </div>

                <div class="card text-muted">
                    <div class="card-header">
                        <h4>
                            <strong>Redes Sociais</strong>
                        </h4>
                    </div>                                
                    <div class="card-body">
                        <div class="row">                                                       
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms text-muted"><b>Facebook:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Facebook"
                                        id="facebook" wire:model="facebook">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms text-muted"><b>Instagram:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Instagram"
                                        id="instagram" wire:model="instagram">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label class="labelforms text-muted"><b>Linkedin:</b></label>
                                    <input type="text" class="form-control text-muted" placeholder="Linkedin"
                                        id="linkedin" wire:model="linkedin">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="labelforms text-muted">
                                    <b>Informações adicionais</b>
                                </label>

                                <textarea
                                    class="form-control"
                                    rows="4"
                                    wire:model.defer="information"
                                    placeholder="Observações, informações internas, anotações do RH..."
                                ></textarea>

                                @error('information')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if (!auth()->user()->isEmployee())
                    <div class="card text-muted">
                        <div class="card-header">
                            <h4>
                                <strong>Permissões & Acesso</strong>
                            </h4>
                        </div>                                
                        <div class="card-body">
                            <div class="row">   
                                
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="form-group">
                                        <label class="labelforms"><b>Cargo</b></label>
                                        <input type="text" class="form-control @error('cargo') is-invalid @enderror" id="cargo" placeholder="Cargo" wire:model="cargo">
                                        @error('cargo')
                                            <span class="error erro-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-check d-inline mx-2">
                                        <input id="employee" class="form-check-input" type="radio"
                                            wire:model.live="roleSelected" value="employee">
                                        <label for="employee">Colaborador</label>
                                    </div>

                                    <div class="form-check d-inline mx-2">
                                        <input id="manager" class="form-check-input" type="radio"
                                            wire:model.live="roleSelected" value="manager">
                                        <label for="manager">Gerente</label>
                                    </div>
                                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                                        <div class="form-check d-inline mx-2">
                                            <input id="admin" class="form-check-input" type="radio"
                                                wire:model.live="roleSelected" value="admin">
                                            <label for="admin">Administrador</label>
                                        </div>
                                    @endif
                                    @if (auth()->user()->isSuperAdmin()) 
                                        <div class="form-check d-inline mx-2">
                                            <input id="superadmin" class="form-check-input" type="radio"
                                                wire:model.live="roleSelected" value="super-admin">
                                            <label for="superadmin">Super Administrador</label>
                                        </div>
                                    @endif
                                    @error('roleSelected')
                                        <div class="text-danger text-sm mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror                                    
                                </div>  
                                @if (!$userId && $roleSelected !== 'employee')
                                    <!-- Campo: Senha -->
                                    <div class="col-12 col-md-6 col-lg-4 mt-3">
                                        <label class="labelforms text-muted"><b>Senha:</b></label>
                                        <div class="input-group input-group-md">                                    
                                            <input type="password" id="code" class="form-control @error('code') is-invalid @enderror" wire:model.defer="code">
                                            <span class="input-group-append">
                                                <button type="button" onclick="togglePassword('code')" class="btn btn-default btn-flat">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </span>
                                        </div>
                                        @error('code') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Campo: Confirmar Senha -->
                                    <div class="col-12 col-md-6 col-lg-4 mt-3">
                                        <label class="labelforms text-muted"><b>Confirmar Senha:</b></label>
                                        <div class="input-group input-group-md">                                    
                                            <input type="password" id="code_confirmation" class="form-control @error('code_confirmation') is-invalid @enderror" wire:model.defer="code_confirmation">
                                            <span class="input-group-append">
                                                <button type="button" onclick="togglePassword('code_confirmation')" class="btn btn-default btn-flat">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </span>
                                        </div>
                                        @error('code_confirmation') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>
                                @endif     
                                
                                
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row text-right">
                    <div class="col-12 pb-4 mt-3">
                        <button type="submit" class="btn btn-lg btn-success p-3"><i class="nav-icon fas fa-check mr-2"></i>{{ $userId ? 'Atualizar Agora' : 'Cadastrar Agora' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('user-atualizado', function() {
        Swal.fire({
            title: 'Sucesso!',
            text: "Usuário atualizado!",
            icon: 'success',
            showConfirmButton: false,
            timer: 3000 // Fecha automaticamente após 3 segundos
        });
    });

    document.addEventListener('user-cadastrado', function() {
        Swal.fire({
            title: 'Sucesso!',
            text: "Usuário Cadastrado!",
            icon: 'success',
            showConfirmButton: false,
            timer: 3000 // Fecha automaticamente após 3 segundos
        });
    });

    function initFlatpickr() {
            let input = document.getElementById('datepicker');
            if (!input) return;

            flatpickr(input, {
                dateFormat: "d/m/Y",
                allowInput: true,
                maxDate: "today",
                defaultDate: input.value || null,
                onChange: function(selectedDates, dateStr) {
                    input.dispatchEvent(new Event('input')); // Força atualização no Alpine.js
                },
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                        longhand: ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'],
                    },
                    months: {
                        shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                        longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                    },
                    today: "Hoje",
                    clear: "Limpar",
                    weekAbbreviation: "Sem",
                    scrollTitle: "Role para aumentar",
                    toggleTitle: "Clique para alternar",
                }
            });
        }

        document.addEventListener("livewire:load", () => {
            initFlatpickr();
        });

        document.addEventListener("livewire:updated", () => {
            initFlatpickr();
        });
    
</script>

<script>

    document.addEventListener('livewire:init', () => {
        // Configurações do Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "4000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "preventDuplicates": false,
            "newestOnTop": true
        };

        Livewire.on('toast', (event) => {
            toastr[event.type](event.message);
        });
        
    });

</script>
<script>  

    function togglePassword(id) {
        let input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>

<script>
    // window.addEventListener('scroll-to-top', event => {
    //     window.scrollTo({ top: 0, behavior: 'smooth' });
    // });
</script>
