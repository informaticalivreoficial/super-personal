<div x-cloak x-data="{
    toasts: [],
    init() {
        const handle = (event) => {
            const d = event.detail;
            this.add((Array.isArray(d) ? d[0] : d) ?? {});
        };
        window.addEventListener('toast', handle);
        window.addEventListener('toastr', handle);
        @if (session()->has('toast'))
            this.add({ type: @js(session('toast.type')), message: @js(session('toast.message')) });
        @endif
    },
    add(toast) {
        if (!toast.message) return;
        const id = (this.toasts.at(-1)?.id ?? 0) + 1;
        this.toasts.push({ id, type: toast.type || 'info', message: toast.message });
        setTimeout(() => this.remove(id), 4500);
    },
    remove(id) {
        this.toasts = this.toasts.filter((t) => t.id !== id);
    },
    styleFor(type) {
        return {
            success: 'border-green-200 bg-green-50 text-green-800',
            error: 'border-red-200 bg-red-50 text-red-800',
            warning: 'border-amber-200 bg-amber-50 text-amber-800',
            info: 'border-sky-200 bg-sky-50 text-sky-800',
        }[type] || 'border-sky-200 bg-sky-50 text-sky-800';
    },
}" class="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg shadow-gray-900/10 backdrop-blur"
            :class="styleFor(toast.type)">
            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center [&>svg]:h-5 [&>svg]:w-5">
                <x-icon name="check" x-show="toast.type === 'success'" />
                <x-icon name="x-mark" x-show="toast.type === 'error'" />
                <x-icon name="exclamation-triangle" x-show="toast.type === 'warning'" />
                <x-icon name="information-circle"
                    x-show="!['success', 'error', 'warning'].includes(toast.type)" />
            </span>
            <p class="flex-1 text-sm font-medium" x-text="toast.message"></p>
            <button type="button" @click="remove(toast.id)" class="shrink-0 opacity-50 transition hover:opacity-100">
                <x-icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
