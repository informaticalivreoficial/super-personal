<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Painel' }} | {{ config('app.name', 'Super Personal') }}</title>

    <link rel="icon" href="{{ asset('images/chave.png') }}" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Editor rico (views de posts/config legadas) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css">

    {{-- Estado da navegação (sidebar) compartilhado com os componentes --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('nav', {
                mini: localStorage.getItem('navMini') === '1',
                mobile: false,
                toggleMini() {
                    this.mini = !this.mini;
                    localStorage.setItem('navMini', this.mini ? '1' : '0');
                },
                openMobile() { this.mobile = true; },
                closeMobile() { this.mobile = false; },
            });
        });
    </script>

    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>

<body class="antialiased">
    <div x-cloak class="min-h-screen">
        <livewire:navigation.side-navigation />

        {{-- Backdrop do menu no mobile --}}
        <div x-show="$store.nav.mobile" x-transition.opacity
            @click="$store.nav.closeMobile()"
            class="fixed inset-0 z-30 bg-gray-950/60 backdrop-blur-sm lg:hidden"></div>

        <div class="flex min-h-screen flex-col transition-[padding] duration-200"
            :class="$store.nav.mini ? 'lg:pl-20' : 'lg:pl-64'">
            <livewire:navigation.top-navigation />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <livewire:navigation.footer />
        </div>
    </div>

    @auth
        <livewire:components.support-modal />
    @endauth
    <livewire:components.toastr-notification />

    {{-- SweetAlert global: eventos dispatch('swal:*') --}}
    <script>
        ['swal', 'swal:error', 'swal:success', 'swal:info', 'swal:warning'].forEach(eventName => {
            window.addEventListener(eventName, (event) => {
                const data = event.detail?.[0] ?? {};

                let defaultIcon = 'info';
                if (eventName === 'swal:error') defaultIcon = 'error';
                if (eventName === 'swal:success') defaultIcon = 'success';
                if (eventName === 'swal:warning') defaultIcon = 'warning';

                Swal.fire({
                    title: data.title ?? 'Aviso',
                    text: data.text ?? '',
                    icon: data.icon ?? defaultIcon,
                    timer: data.timer ?? null,
                    showConfirmButton: data.showConfirmButton ?? true,
                    confirmButtonText: data.confirmButtonText ?? 'OK',
                });
            });
        });

        window.addEventListener('swal:confirm', (event) => {
            const data = event.detail?.[0] ?? {};

            Swal.fire({
                title: data.title ?? 'Tem certeza?',
                text: data.text ?? '',
                icon: data.icon ?? 'warning',
                showCancelButton: true,
                confirmButtonText: data.confirmButtonText ?? 'Confirmar',
                cancelButtonText: data.cancelButtonText ?? 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed && data.confirmEvent) {
                    Livewire.dispatch(data.confirmEvent, data.confirmParams ?? []);
                }
            });
        });
    </script>

    {{-- Editor Quill integrado ao Livewire (views legadas) --}}
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.min.js"></script>
    <script src="https://unpkg.com/quill-image-resize-module/image-resize.min.js"></script>
    <script>
        if (typeof ImageResize !== 'undefined') {
            Quill.register('modules/imageResize', ImageResize.default, true);
        }
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('quillEditor', ({ value, model }) => ({
                quill: null,

                init() {
                    if (this.quill) return; // 🔥 evita duplicar editor

                    // 🔥 Registrar módulo de redimensionamento
                    // if (typeof ImageResize !== 'undefined') {
                    //     Quill.register('modules/imageResize', ImageResize.default);
                    // }

                    this.quill = new Quill(this.$refs.editor, {
                        theme: 'snow',
                        placeholder: 'Digite aqui...',
                        modules: {
                            toolbar: [
                                [{ header: [1, 2, 3, false] }],
                                [{ font: [] }, { size: ['small', false, 'large', 'huge'] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ color: [] }, { background: [] }],
                                [{ align: [] }],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['blockquote'],
                                ['link', 'image'],
                                ['clean'], 
                            ],
                            // 🖼️ Módulo de redimensionamento visual
                            imageResize: {
                                displaySize: true,
                                modules: ['Resize', 'DisplaySize']
                            }
                        },
                    });

                    // 🔥 SCROLL AQUI
                    const editorEl = this.$refs.editor.querySelector('.ql-editor');
                    editorEl.style.maxHeight = '350px';
                    editorEl.style.overflowY = 'auto';

                    // Conteúdo inicial (edit)
                    if (value) {
                        this.quill.root.innerHTML = value;
                    }

                    // 🔥 SINCRONIZAÇÃO INICIAL (create FIX)
                    this.sync();

                    // Atualização ao digitar
                    this.quill.on('text-change', () => {
                        this.sync();
                    });

                    // Adicionar suporte a alinhamento de imagens
                    this.addImageAlignmentSupport();
                },

                sync() {
                    const html = this.quill.root.innerHTML;
                    const componentEl = this.$el.closest('[wire\\:id]');

                    if (!componentEl || typeof Livewire === 'undefined') return;

                    const component = Livewire.find(componentEl.getAttribute('wire:id'));
                    if (component) {
                        component.set(model, html, false);
                    }
                },

                addImageAlignmentSupport() {
                    this.quill.root.addEventListener('click', (e) => {
                        if (e.target.tagName === 'IMG') {
                            const parent = e.target.closest('p');
                            if (parent) {
                                const alignment = parent.className.match(/ql-align-(\w+)/);
                                if (alignment) {
                                    const alignType = alignment[1];
                                    this.applyImageAlignment(e.target, alignType);
                                }
                            }
                        }
                    });

                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                const target = mutation.target;
                                const img = target.querySelector('img');
                                if (img) {
                                    const alignment = target.className.match(/ql-align-(\w+)/);
                                    if (alignment) {
                                        this.applyImageAlignment(img, alignment[1]);
                                    }
                                }
                            }
                        });
                    });

                    observer.observe(this.quill.root, {
                        attributes: true,
                        attributeFilter: ['class'],
                        subtree: true
                    });
                },

                applyImageAlignment(img, alignment) {
                    img.style.marginLeft = '';
                    img.style.marginRight = '';
                    img.style.display = 'block';

                    switch(alignment) {
                        case 'center':
                            img.style.marginLeft = 'auto';
                            img.style.marginRight = 'auto';
                            break;
                        case 'right':
                            img.style.marginLeft = 'auto';
                            img.style.marginRight = '0';
                            break;
                        case 'left':
                            img.style.marginLeft = '0';
                            img.style.marginRight = 'auto';
                            break;
                    }

                    this.sync();
                },
            }));
        });
    </script>

    @stack('scripts')
</body>

</html>
