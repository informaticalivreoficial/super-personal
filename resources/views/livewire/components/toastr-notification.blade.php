<div></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toast via Livewire
        window.addEventListener('toast', event => {
            const { type, message } = event.detail[0];
            showToast(type, message);
        });

        // Toast via session (redirect)
        @if (session()->has('toast'))
            showToast(
                "{{ session('toast.type') }}",
                "{{ session('toast.message') }}"
            );
        @endif

        function showToast(type, message) {
            const colors = {
                success: '#16a34a',
                error: '#dc2626',
                warning: '#f59e0b',
                info: '#2563eb',
            };

            Toastify({
                text: message,
                duration: 4000,
                gravity: "top",
                position: "right",
                close: true,
                style: {
                    background: colors[type] ?? '#2563eb',
                },
            }).showToast();
        }
    });
</script>