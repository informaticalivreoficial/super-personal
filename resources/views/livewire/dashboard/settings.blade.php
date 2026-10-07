<div x-data="{ open: false }" x-cloak>
    @section('title', $title)
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cog mr-2"></i> Configurações</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">                    
                        <li class="breadcrumb-item"><a href="{{route('admin')}}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Configurações</li>
                    </ol>
                </div>
            </div>
        </div>    
    </div>

    
    <div x-data="{
            tab: @entangle('currentTab'),
            init() {
                if (!this.tab) this.tab = 'dados';

                this.$watch('tab', () => {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const erroEl = [...document.querySelectorAll('[x-ref]')].find(el =>
                                el.querySelector('.erro-feedback')
                            );

                            if (erroEl) {
                                erroEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 50);
                    });
                });
            }
        }" class="w-full">
        <!-- Abas -->
        <div class="flex space-x-2 border-b border-green-300">
            <button type="button"
                    class="px-4 py-4 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'dados' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'dados'">
                📝 Dados
            </button>
            <button type="button"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'seo' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'seo'">
                📷 Seo
            </button>
            <button type="button"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'contato' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'contato'">
                📷 Informações de Contato
            </button>
            <button type="button"
                    class="px-4 py-2 text-sm font-medium rounded-t-lg focus:outline-none transition-all duration-200"
                    :class="tab === 'imagens' ? 'bg-white border-l border-t border-r text-blue-600' : 'text-gray-500 hover:text-blue-500'"
                    @click="tab = 'imagens'">
                📷 Imagens
            </button>
        </div>

        <div class="card-body text-muted bg-white">
            <form wire:submit.prevent="update" autocomplete="off"> 
                <!-- Conteúdo da aba Dados -->
                <div x-show="tab === 'dados'" class="bg-white" x-transition>                    
                    <div class="row">  
                        <div class="col-12 col-md-6 col-lg-12"> 
                            <div class="row mb-2 text-muted">
                                <div class="col-12 col-md-6 col-sm-6 col-lg-6 mb-2" x-ref="configData_app_name">
                                    <div class="form-group">
                                        <label class="labelforms"><b>Nome do site</b></label> 
                                        <input type="text" class="form-control @error('configData.app_name') is-invalid @enderror" placeholder="Nome do site" wire:model="configData.app_name" id="app_name">
                                        @error('configData.app_name')
                                            <span class="error erro-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-sm-6 col-lg-6 mb-2">
                                    @if(\Illuminate\Support\Facades\Auth::user()->isAdmin() || \Illuminate\Support\Facades\Auth::user()->isSuperAdmin())
                                        <div class="form-group">
                                            <label class="labelforms"><b>URL do site</b></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="URL do site"  wire:model="configData.domain"/>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <a href="#" @click.prevent="open = true" title="QrCode"><i class="fa fa-qrcode"></i></a>
                                                    </div>                                                            
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="form-group">
                                            <label class="labelforms"><b>URL do site</b></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="URL do site" wire:model="configData.domain" disabled>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <a href="#" @click.prevent="open = true" title="QrCode">
                                                            <i class="fa fa-qrcode"></i>
                                                        </a>
                                                    </div>                                                            
                                                </div>
                                            </div>
                                        </div>
                                    @endif                                                    
                                </div>                                         
                            </div>                                           
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body text-muted">
                            <div class="row mb-2">
                                <div class="col-12 col-md-6 col-lg-2" x-ref="configData_zipcode"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*CEP:</b></label>
                                        <input type="text" x-mask="99.999-999" class="form-control @error('configData.zipcode') is-invalid @enderror" id="zipcode" wire:model.lazy="configData.zipcode">
                                        @error('configData.zipcode')
                                            <span class="error erro-feedback">{{ $message }}</span>
                                        @enderror                                                    
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-4 col-lg-3"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*Estado:</b></label>
                                        <input type="text" class="form-control" id="state" wire:model="configData.state" readonly>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 col-lg-4"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*Cidade:</b></label>
                                        <input type="text" class="form-control" id="city" wire:model="configData.city" readonly>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*Rua:</b></label>
                                        <input type="text" class="form-control" id="street" wire:model="configData.street" readonly>
                                    </div>
                                </div>                                            
                            </div>
                            <div class="row mb-2">
                                <div class="col-12 col-md-4 col-lg-3"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*Bairro:</b></label>
                                        <input type="text" class="form-control" id="neighborhood" wire:model="configData.neighborhood" readonly>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-2"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>*Número:</b></label>
                                        <input type="text" class="form-control" placeholder="Número do Endereço" id="number" wire:model="configData.number">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>Complemento:</b></label>
                                        <input type="text" class="form-control" id="complement" wire:model="configData.complement"/>
                                    </div>
                                </div>   
                            </div>
                        </div>
                    </div>
                    <div class="card">                                
                        <div class="card-body text-muted">
                            <div class="row mb-2">                                                        
                                <div class="col-12 col-md-4 col-sm-4 col-lg-4"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>CNPJ:</b></label>
                                        <input type="text" class="form-control cnpjmask" placeholder="CNPJ" wire:model="configData.cnpj" id="cnpj">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 col-sm-4 col-lg-4"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>Inscrição Estadual:</b></label>
                                        <input type="text" class="form-control" placeholder="Inscrição Estadual" wire:model="configData.ie" id="ie">
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 col-sm-4 col-lg-4"> 
                                    <div class="form-group">
                                        <label class="labelforms"><b>Ano de ínicio</b></label>
                                        <input type="text" class="form-control" placeholder="Ano de ínicio" wire:model="configData.init_date" id="init_date">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-12 pt-4" wire:ignore>   
                            <label class="labelforms text-muted"><b>Política de Privacidade</b></label>
                            <x-editor-quill 
                                :value="$configData['privacy_policy']" 
                                model="configData.privacy_policy" 
                            />                                                                                     
                        </div>                                    
                    </div>                                       
                    <div class="row mb-2">
                        <div class="col-12 pt-4" wire:ignore>   
                            <label class="labelforms text-muted"><b>Termos e Condições</b></label>
                            <x-editor-quill 
                                :value="$configData['terms_condicions']" 
                                model="configData.terms_condicions" 
                            />                                                                                     
                        </div>                                    
                    </div>                                        
                </div> 

                <!-- Conteúdo da aba Seo -->
                <div x-show="tab === 'seo'" class="bg-white" x-cloak x-transition>                    
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="mb-6">                                    
                                <h5 class="text-lg font-semibold text-gray-600">Descrição do site::</h5>                                    
                            </div>
                        </div>
                        <div class="col-12 mb-1"> 
                            <div class="form-group">
                                <textarea class="form-control" rows="5" wire:model="configData.information">{{ $configData['information'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-6">                                    
                                <h5 class="text-lg font-semibold text-gray-600">MetaTags::</h5>                                    
                            </div>
                        </div>
                        <div class="col-12 mb-1"> 
                            <div class="form-group">
                                <div 
                                    x-data="{
                                        tags: @entangle('tags'),
                                        input: '',
                                        addTag() {
                                            const trimmed = this.input.trim();
                                            if (trimmed && !this.tags.includes(trimmed)) {
                                                this.tags.push(trimmed);
                                            }
                                            this.input = '';
                                        },
                                        removeTag(index) {
                                            this.tags.splice(index, 1);
                                        }
                                    }"
                                    class="p-4 border rounded shadow"
                                    >
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <template x-for="(tag, index) in tags" :key="index">
                                            <span class="flex items-center bg-blue-500 text-white px-3 py-1 rounded-full">
                                                <span x-text="tag"></span>
                                                <button type="button" @click="removeTag(index)" class="ml-2 hover:text-gray-200">&times;</button>
                                            </span>
                                        </template>
                                    </div>                                    
                                    <input 
                                        type="text" 
                                        x-model="input" 
                                        @keydown.enter.prevent="addTag"
                                        placeholder="Digite uma tag e pressione Enter"
                                        class="border border-gray-300 rounded px-3 py-2 w-full"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-6">                                    
                                <h5 class="text-lg font-semibold text-gray-600">Redes Sociais::</h5>                                    
                            </div>
                        </div>                            
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Facebook:</b></label>
                                <input type="text" class="form-control" placeholder="Facebook" wire:model="configData.facebook" id="facebook">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Twitter:</b></label>
                                <input type="text" class="form-control" placeholder="Twitter" wire:model="configData.twitter" id="twitter">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Youtube:</b></label>
                                <input type="text" class="form-control" placeholder="Youtube" wire:model="configData.youtube" id="youtube">
                            </div>
                        </div>                        
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Instagram:</b></label>
                                <input type="text" class="form-control" placeholder="Instagram" wire:model="configData.instagram" id="instagram">
                            </div>
                        </div>                        
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Linkedin:</b></label>
                                <input type="text" class="form-control" placeholder="Linkedin" wire:model="configData.linkedin" id="linkedin">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="mb-6 prose max-w-none">  
                                <hr class="h-px my-3 bg-gray-200 border-0 dark:bg-gray-700">                                  
                                <h5 class="text-lg font-semibold text-gray-600">Google Maps::</h5>                                    
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-sm-6 col-lg-6">   
                            <div class="form-group">
                                <label class="labelforms"><b>Mapa do Google</b> <small class="text-info">(Copie o código de incorporação do Google Maps e cole abaixo)</small></label>
                                <textarea id="inputDescription" class="form-control" rows="14" wire:model="configData.maps_google">{{ $configData['maps_google'] ?? '' }}</textarea> 
                            </div>                                                     
                        </div>
                        <div class="col-12 col-md-6 col-sm-6 col-lg-6 mapa-google mb-3"> 
                            {!! $configData['maps_google'] ?? '' !!}
                        </div>
                    </div>                    
                </div>

                <!-- Conteúdo da aba contato -->
                <div x-show="tab === 'contato'" class="bg-white" x-cloak x-transition>                    
                    <div class="row p-4 border rounded shadow">
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Telefone fixo:</b></label>
                                <input type="text" class="form-control" placeholder="(00) 0000-0000"
                                    x-mask="(99) 9999-9999" wire:model="configData.phone" id="phone">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>*Celular:</b></label>
                                <input type="text" class="form-control" placeholder="(00) 00000-0000"
                                    x-mask="(99) 99999-9999" wire:model="configData.cell_phone"
                                    id="cell_phone">                                    
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>WhatsApp:</b></label>
                                <input type="text" class="form-control" placeholder="(00) 00000-0000"
                                    x-mask="(99) 99999-9999" wire:model="configData.whatsapp"
                                    id="whatsapp">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Email:</b></label>
                                <input type="text" class="form-control" placeholder="Email" 
                                    wire:model="configData.email" id="email">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Email Adicional:</b></label>
                                <input type="text" class="form-control" placeholder="Email Alternativo" 
                                    wire:model="configData.additional_email" id="additional_email">
                            </div>
                        </div>                            
                        <div class="col-12 col-md-6 col-lg-4"> 
                            <div class="form-group">
                                <label class="labelforms"><b>Telegram:</b></label>
                                <input type="text" class="form-control" placeholder="Telegram" 
                                    wire:model="configData.telegram" id="telegram">
                            </div>
                        </div>                            
                    </div>                    
                </div>

                <!-- Conteúdo da aba Imagens -->
                <div x-show="tab === 'imagens'" class="bg-white" x-cloak x-transition> 
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-3">
                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Logo do site</b> - {{ env('LOGOMARCA_WIDTH') }}x{{ env('LOGOMARCA_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $logo }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];

                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result; // Atualiza o preview
                                            };
                                            reader.readAsDataURL(file);
                                        }

                                        // Reseta o input para permitir o mesmo arquivo novamente
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('LOGOMARCA_WIDTH', 200) }}" 
                                    height="{{ env('LOGOMARCA_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="logo" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.live="logo"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="logo"
                                />
                            </div>
                        </div> 
                    
                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Logo do Gerenciador</b> - {{ env('LOGOMARCA_GERENCIADOR_WIDTH') }}x{{ env('LOGOMARCA_GERENCIADOR_HEIGHT') }} pixels
                            </label>
                            
                            <div 
                                x-data="{
                                    preview: '{{ $logo_admin }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];

                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result; // Atualiza o preview
                                            };
                                            reader.readAsDataURL(file);
                                        }

                                        // Reseta o input para permitir o mesmo arquivo novamente
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('LOGOMARCA_GERENCIADOR_WIDTH', 200) }}" 
                                    height="{{ env('LOGOMARCA_GERENCIADOR_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="logo_admin" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="logo_admin"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="logo_admin"
                                />

                            </div>
                        </div>

                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Logo do rodapé</b> - {{ env('LOGOMARCA_FOOTER_WIDTH') }}x{{ env('LOGOMARCA_FOOTER_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $logo_footer }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];

                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result; // Atualiza o preview
                                            };
                                            reader.readAsDataURL(file);
                                        }

                                        // Reseta o input para permitir o mesmo arquivo novamente
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('LOGOMARCA_FOOTER_WIDTH', 200) }}" 
                                    height="{{ env('LOGOMARCA_FOOTER_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="logo_footer" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="logo_footer"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="logo_footer"
                                />
                            </div>
                        </div>

                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Favicon</b> - {{ env('FAVEICON_WIDTH') }}x{{ env('FAVEICON_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $favicon }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result;
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('FAVEICON_WIDTH', 200) }}" 
                                    height="{{ env('FAVEICON_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="favicon" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="favicon"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="favicon"
                                />
                            </div>
                        </div>

                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Marca D´agua</b> - {{ env('MARCADAGUA_WIDTH') }}x{{ env('MARCADAGUA_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $watermark }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result;
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('MARCADAGUA_WIDTH', 200) }}" 
                                    height="{{ env('MARCADAGUA_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="watermark" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="watermark"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="watermark"
                                />
                            </div>
                        </div>

                    </div> 
                    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 mt-3">
                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Meta Imagem: </b> - {{ env('METAIMG_WIDTH') }}x{{ env('METAIMG_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $metaimg }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result;
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('METAIMG_WIDTH', 200) }}" 
                                    height="{{ env('METAIMG_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="metaimg" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="metaimg"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="metaimg"
                                />
                            </div>
                        </div>
                        <div class="p-4 border rounded shadow">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <b>Topo do site: </b> - {{ env('IMGHEADER_WIDTH') }}x{{ env('IMGHEADER_HEIGHT') }} pixels
                            </label>
                        
                            <div 
                                x-data="{
                                    preview: '{{ $imgheader }}',
                                    updatePreview(event) {
                                        const fileInput = event.target;
                                        const file = fileInput.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                this.preview = e.target.result;
                                            };
                                            reader.readAsDataURL(file);
                                        }
                                        fileInput.value = '';
                                    }
                                }"
                                class="flex flex-col items-start space-y-2">

                                <img 
                                    :src="preview" 
                                    alt="Preview"
                                    class="border rounded max-w-full h-auto"
                                    width="{{ env('IMGHEADER_WIDTH', 200) }}" 
                                    height="{{ env('IMGHEADER_HEIGHT', 100) }}"
                                >

                                <div 
                                    wire:loading wire:target="imgheader" 
                                    class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded"
                                >
                                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>

                                <input 
                                    type="file" 
                                    @change="updatePreview"
                                    wire:model.defer="imgheader"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                    id="imgheader"
                                />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row text-right mt-3">
                    <div class="col-12 mb-4">
                        <button type="button" wire:click="update" class="btn btn-lg btn-success p-3">
                            <i class="nav-icon fas fa-check mr-2"></i> Atualizar Configurações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

        
    <!-- Modal -->
    <div x-show="open" x-cloak
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        
        <div @click.outside="open = false"
            class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 relative transition-all duration-300"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90">
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">QrCode do site</h2>
                <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Conteúdo -->
            <div class="text-center">
                <p class="mb-2 text-gray-600">Este QrCode direciona para:</p>
                <p class="text-sm font-semibold text-blue-600 mb-4">
                    {{ $this->configData['domain'] ?? env('DESENVOLVEDOR_URL') }}
                </p>
                <div class="flex justify-center">
                    <img src="data:image/svg+xml;utf8,{{ rawurlencode($this->qrCodeSvg) }}">
                </div>
            </div>

            <!-- Rodapé -->
            <div class="mt-6 text-right">
                <button @click="open = false" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                    Fechar
                </button>
            </div>
        </div>
    </div>

</div>

<script>

    document.addEventListener('atualizado', function() {
        Swal.fire({
            title: 'Sucesso!',
            text: "Configurações atualizadas com sucesso!",
            icon: 'success',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: 3000 // Fecha automaticamente após 3 segundos
        });
    });   
    

    function tagInputComponent(tagsBinding) {
        return {
            tags: tagsBinding,
            input: '',
            addTag() {
                const trimmed = this.input.trim();
                if (trimmed && !this.tags.includes(trimmed)) {
                    this.tags.push(trimmed);
                }
                this.input = '';
            },
            removeTag(index) {
                this.tags.splice(index, 1);
            }
        };
    }

    
    
</script>