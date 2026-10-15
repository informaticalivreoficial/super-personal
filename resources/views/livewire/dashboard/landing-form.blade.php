<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate
                href="{{ auth()->user()->isPlatformAdmin() ? route('professors.index') : route('admin') }}"
                title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="link" class="h-6 w-6 text-brand-600" />
                    Minha página pública
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ auth()->user()->isPlatformAdmin() ? 'Plataforma / Landing do treinador' : 'Perfil / Landing page' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Status da publicação --}}
    @if ($isPublished)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
            <p class="flex items-center gap-2 text-sm text-green-800">
                <x-icon name="check-badge" class="h-5 w-5" />
                Sua página está <strong>publicada</strong>.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <span class="flex items-center gap-1.5 text-sm text-green-800" title="Total de acessos de visitantes à sua página pública">
                    <x-icon name="eye" class="h-4 w-4" />
                    <strong>{{ number_format($landingViews, 0, ',', '.') }}</strong>
                    {{ $landingViews === 1 ? 'acesso' : 'acessos' }}
                </span>
                <a href="{{ $landingUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                    {{ $landingUrl }}
                    <x-icon name="arrow-top-right-on-square" class="h-4 w-4" />
                </a>
            </div>
        </div>
    @else
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="flex items-center gap-2 text-sm text-gray-600">
                <x-icon name="eye" class="h-5 w-5 text-gray-400" />
                Sua página está <strong>oculta</strong> — marque "Publicar" abaixo para deixá-la no ar.
            </p>
            <span class="flex items-center gap-1.5 text-sm text-gray-500" title="Total de acessos de visitantes à sua página pública">
                <x-icon name="eye" class="h-4 w-4" />
                <strong>{{ number_format($landingViews, 0, ',', '.') }}</strong>
                {{ $landingViews === 1 ? 'acesso' : 'acessos' }}
            </span>
        </div>
    @endif

    <form wire:submit="save" autocomplete="off" class="space-y-6">

        {{-- Conteúdo público --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Conteúdo público</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_slug">Endereço da página</label>
                            <div class="flex">
                                <span
                                    class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                    /treinador/
                                </span>
                                <input type="text" class="form-control rounded-l-none @error('slug') is-invalid @enderror"
                                    id="landing_slug" wire:model="slug" placeholder="{{ $exampleSlug }}">
                            </div>
                            @error('slug')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Letras minúsculas, números e hífens. Deixe em branco para gerar do seu nome
                                (ex.: <strong>{{ $exampleSlug }}</strong>).
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_whatsapp">WhatsApp (CTA de contato)</label>
                            <input type="text" class="form-control @error('whatsapp') is-invalid @enderror"
                                id="landing_whatsapp" wire:model="whatsapp"
                                placeholder="Ex.: 11 99999-8888 (com DDI, se fora do Brasil)">
                            @error('whatsapp')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Usado no botão "Falar no WhatsApp". Sem DDI, assume +55.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="landing_title">Título da página</label>
                            <input type="text" class="form-control @error('landing_title') is-invalid @enderror"
                                id="landing_title" wire:model="landingTitle"
                                placeholder="Ex.: Fit Club Triathlon (opcional)">
                            @error('landing_title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                É o grande título no topo da página pública — use o nome da sua empresa
                                ou marca se preferir. Deixe em branco para exibir seu nome.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="landing_tagline">Frase de eixo</label>
                            <input type="text" class="form-control @error('tagline') is-invalid @enderror"
                                id="landing_tagline" wire:model="tagline"
                                placeholder="Ex.: Triathlon & Corrida — treinos para sua prova">
                            @error('tagline')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="landing_bio">Sobre você</label>
                            <textarea rows="4" class="form-control @error('bio') is-invalid @enderror"
                                id="landing_bio" wire:model="bio"
                                placeholder="Conte sua história, metodologia e resultados..."></textarea>
                            @error('bio')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_specialty">Especialidade</label>
                            <input type="text" class="form-control" id="landing_specialty"
                                wire:model="specialty" maxlength="255"
                                placeholder="Ex.: Triathlon, Corrida de Rua, Musculação...">
                            @error('specialty')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_cref">CREF</label>
                            <input type="text" class="form-control" id="landing_cref"
                                wire:model="cref" maxlength="30"
                                placeholder="Ex.: 123456-G/SP">
                            @error('cref')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="flex items-start gap-3 rounded-lg bg-brand-50 p-4">
                            <input type="checkbox" wire:model="publicProfile"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="text-sm font-semibold text-gray-900">Publicar minha página</span>
                                <span class="block text-xs text-gray-500">
                                    Com publicada, qualquer pessoa com o endereço acessa seu perfil público.
                                    Alunos, treinos e pagamentos nunca aparecem na página.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Identidade visual --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Identidade visual</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- Foto de perfil --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="landing_profile_photo">Foto de perfil</label>
                            <div class="space-y-2">
                                @if ($imagePreviews['profile_photo'])
                                    <img src="{{ $imagePreviews['profile_photo'] }}" alt="Foto de perfil"
                                        class="h-20 w-20 rounded-full border border-gray-200 bg-white object-cover">
                                @endif
                                <input type="file" id="landing_profile_photo" wire:model="profilePhoto" accept="image/*"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <span wire:loading wire:target="profilePhoto" class="text-xs text-gray-500">Enviando…</span>
                                @error('profilePhoto')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                @if ($hasSavedImage['profile_photo'])
                                    <label class="flex items-center gap-2 text-xs text-gray-500">
                                        <input type="checkbox" wire:model="removeProfilePhoto"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remover foto
                                    </label>
                                @endif
                                <p class="text-xs text-gray-500">Exibida no topo da página. Até 2 MB (PNG/JPG).</p>
                            </div>
                        </div>
                    </div>

                    {{-- Logo --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="landing_logo">Logo</label>
                            <div class="space-y-2">
                                @if ($imagePreviews['logo'])
                                    <img src="{{ $imagePreviews['logo'] }}" alt="Logo"
                                        class="h-16 w-auto rounded-lg border border-gray-200 bg-white p-1 object-contain">
                                @endif
                                <input type="file" id="landing_logo" wire:model="logo" accept="image/*"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <span wire:loading wire:target="logo" class="text-xs text-gray-500">Enviando…</span>
                                @error('logo')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                @if ($hasSavedImage['logo'])
                                    <label class="flex items-center gap-2 text-xs text-gray-500">
                                        <input type="checkbox" wire:model="removeLogo"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remover logo
                                    </label>
                                @endif
                                <p class="text-xs text-gray-500">Cabeçalho da página. Até 2 MB (PNG/JPG).</p>
                            </div>
                        </div>
                    </div>

                    {{-- Favicon --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="landing_favicon">Favicon</label>
                            <div class="space-y-2">
                                @if ($imagePreviews['favicon'])
                                    <img src="{{ $imagePreviews['favicon'] }}" alt="Favicon"
                                        class="h-10 w-10 rounded border border-gray-200 bg-white p-1 object-contain">
                                @endif
                                <input type="file" id="landing_favicon" wire:model="favicon" accept="image/*"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <span wire:loading wire:target="favicon" class="text-xs text-gray-500">Enviando…</span>
                                @error('favicon')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                @if ($hasSavedImage['favicon'])
                                    <label class="flex items-center gap-2 text-xs text-gray-500">
                                        <input type="checkbox" wire:model="removeFavicon"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remover favicon
                                    </label>
                                @endif
                                <p class="text-xs text-gray-500">Ícone da aba do navegador. Até 512 KB.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Metaimg --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="landing_metaimg">Imagem ao compartilhar</label>
                            <div class="space-y-2">
                                @if ($imagePreviews['metaimg'])
                                    <img src="{{ $imagePreviews['metaimg'] }}" alt="Imagem de compartilhamento"
                                        class="h-16 w-auto rounded-lg border border-gray-200 bg-white p-1 object-contain">
                                @endif
                                <input type="file" id="landing_metaimg" wire:model="metaimg" accept="image/*"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <span wire:loading wire:target="metaimg" class="text-xs text-gray-500">Enviando…</span>
                                @error('metaimg')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                @if ($hasSavedImage['metaimg'])
                                    <label class="flex items-center gap-2 text-xs text-gray-500">
                                        <input type="checkbox" wire:model="removeMetaimg"
                                            class="h-3.5 w-3.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remover imagem
                                    </label>
                                @endif
                                <p class="text-xs text-gray-500">Prévia no WhatsApp/Google (og:image). Até 4 MB.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Redes sociais --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Redes sociais</h2>
            </div>

            <div class="card-body">
                <p class="mb-4 text-xs text-gray-500">
                    Cole a URL completa do seu perfil — os links aparecem na seção
                    "Onde me encontrar" da página pública.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_facebook">Facebook</label>
                            <input type="text" class="form-control @error('facebook') is-invalid @enderror"
                                id="landing_facebook" wire:model="facebook"
                                placeholder="https://facebook.com/seuperfil">
                            @error('facebook')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_instagram">Instagram</label>
                            <input type="text" class="form-control @error('instagram') is-invalid @enderror"
                                id="landing_instagram" wire:model="instagram"
                                placeholder="https://instagram.com/seuperfil">
                            @error('instagram')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_twitter">Twitter / X</label>
                            <input type="text" class="form-control @error('twitter') is-invalid @enderror"
                                id="landing_twitter" wire:model="twitter"
                                placeholder="https://x.com/seuperfil">
                            @error('twitter')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_youtube">YouTube</label>
                            <input type="text" class="form-control @error('youtube') is-invalid @enderror"
                                id="landing_youtube" wire:model="youtube"
                                placeholder="https://youtube.com/@seucanal">
                            @error('youtube')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="landing_linkedin">LinkedIn</label>
                            <input type="text" class="form-control @error('linkedin') is-invalid @enderror"
                                id="landing_linkedin" wire:model="linkedin"
                                placeholder="https://linkedin.com/in/seuperfil">
                            @error('linkedin')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Endereço --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Endereço</h2>
            </div>

            <div class="card-body">
                <label class="mb-5 flex items-start gap-3 rounded-lg bg-brand-50 p-4">
                    <input type="checkbox" wire:model="displayAddress"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="text-sm font-semibold text-gray-900">Mostrar endereço na página pública</span>
                        <span class="block text-xs text-gray-500">
                            Enquanto desmarcado, o endereço fica salvo mas não aparece para os visitantes.
                        </span>
                    </span>
                </label>

                {{-- Endereço auto-preenchido via ViaCEP (componente global x-via-cep) --}}
                <x-via-cep>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="landing_zipcode">CEP</label>
                                <div class="relative">
                                    <input type="text"
                                        class="form-control pr-9 @error('zipcode') is-invalid @enderror"
                                        id="landing_zipcode" wire:model="zipcode" data-via-cep="zipcode"
                                        placeholder="00000-000" inputmode="numeric">
                                    <span x-show="loading" x-cloak
                                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                        <x-icon name="spinner" class="h-4 w-4 animate-spin" />
                                    </span>
                                </div>
                                @error('zipcode')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Preenche rua, bairro, cidade e UF.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="landing_street">Rua</label>
                                <input type="text" class="form-control @error('street') is-invalid @enderror"
                                    id="landing_street" wire:model="street" data-via-cep="street"
                                    placeholder="Rua, avenida...">
                                @error('street')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="landing_number">Número</label>
                                <input type="text" class="form-control @error('number') is-invalid @enderror"
                                    id="landing_number" wire:model="number" placeholder="123">
                                @error('number')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="landing_complement">Complemento</label>
                                <input type="text" class="form-control @error('complement') is-invalid @enderror"
                                    id="landing_complement" wire:model="complement" placeholder="Apto, sala... (opcional)">
                                @error('complement')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="landing_neighborhood">Bairro</label>
                                <input type="text" class="form-control @error('neighborhood') is-invalid @enderror"
                                    id="landing_neighborhood" wire:model="neighborhood" data-via-cep="neighborhood">
                                @error('neighborhood')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="landing_city">Cidade</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                    id="landing_city" wire:model="city" data-via-cep="city">
                                @error('city')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label for="landing_state">UF</label>
                                <input type="text" class="form-control @error('state') is-invalid @enderror"
                                    id="landing_state" wire:model="state" data-via-cep="state"
                                    maxlength="2" placeholder="SP">
                                @error('state')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-via-cep>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-icon name="check" class="h-4 w-4" /> Salvar alterações
                </span>
                <span wire:loading wire:target="save">
                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                </span>
            </button>
        </div>
    </form>
</div>
