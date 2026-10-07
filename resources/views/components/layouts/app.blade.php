<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield( 'title', env('APP_NAME') )</title>

    <link rel="icon" href="{{ asset('theme/images/chave.png')}}" type="image/x-icon">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="{{ asset('theme/plugins/fontawesome-free/css/all.min.css') }}">
    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="{{ asset('theme/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">

    <link rel="stylesheet" href="{{ asset('theme/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    {{-- JQVMap --}}
    <link rel="stylesheet" href="{{ asset('theme/plugins/jqvmap/jqvmap.min.css') }}">

    
    {{-- Theme style --}}
    <link rel="stylesheet" href="{{ asset('theme/dist/css/adminlte.min.css') }}">
    {{-- overlayScrollbars --}}
    <link rel="stylesheet" href="{{ asset('theme/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    
    {{-- Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    
    {{-- Toastr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    {{-- General Styles --}}
    <link rel="stylesheet" href="{{ asset('theme/dist/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/dist/css/action-buttons.css') }}">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basiclightbox@5/dist/basicLightbox.min.css">

    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <style>
        .basicLightbox {
            z-index: 9999 !important;
        }

        .basicLightbox__placeholder {
            z-index: 9999 !important;
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="hold-transition sidebar-mini text-sm">
    <div class="wrapper">
        <livewire:navigation.top-navigation />

        <livewire:navigation.side-navigation />

        <div class="content-wrapper">
            {{-- <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard v1</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div> --}}

            <section class="content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </section>
        </div>

        <livewire:navigation.footer />
    </div>

    @auth
        <livewire:components.support-modal />
        <livewire:components.toastr-notification />
    @endauth

    {{-- jQuery --}}
    <script src="{{ asset('theme/plugins/jquery/jquery.min.js') }}"></script> 

    {{-- Bootstrap 4 --}}
    <script src="{{ asset('theme/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script src="{{ asset('theme/plugins/sparklines/sparkline.js') }}"></script>

    {{-- JQVMap --}}
    <script src="{{ asset('theme/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
    
    {{-- daterangepicker --}}
    <script src="{{ asset('theme/plugins/moment/moment.min.js') }}"></script>

    {{-- Tempusdominus Bootstrap 4 --}}
    <script src="{{ asset('theme/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

    
    {{-- Tom Select --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    {{-- overlayScrollbars --}}
    <script src="{{ asset('theme/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>

    <script src="{{ asset('theme/dist/js/adminlte.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Toastr --}}
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script src="https://cdn.jsdelivr.net/npm/basiclightbox@5/dist/basicLightbox.min.js"></script>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://unpkg.com/quill-image-resize-module/image-resize.min.js"></script>

    {{-- 👈 Registra UMA vez aqui, antes do alpine:init --}}
    <script>
        if (typeof ImageResize !== 'undefined') {
            Quill.register('modules/imageResize', ImageResize.default, true);
        }
    </script>

    @stack('scripts') 

    <script>
        // Listener genérico para todos os tipos de SweetAlert
        ['swal', 'swal:error', 'swal:success', 'swal:info', 'swal:warning'].forEach(eventName => {
            window.addEventListener(eventName, (event) => {
                const data = event.detail?.[0] ?? {};

                // Define o ícone baseado no tipo de evento
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

        // Listener para confirmação (precisa de lógica especial)
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
</body>
</html>