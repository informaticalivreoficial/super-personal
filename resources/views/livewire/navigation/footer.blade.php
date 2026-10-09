<footer class="border-t border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
    <div class="flex flex-col items-center justify-between gap-2 text-xs text-gray-500 sm:flex-row">
        <span>&copy; {{ date('Y') }} {{ $config->app_name ?? config('app.name') }}. Todos os direitos reservados.</span>
        <span>
            Feito com ❤ por
            <a href="{{ config('app.desenvolvedor_url') }}" target="_blank" class="font-medium text-brand-600 hover:text-brand-700">
                Informática Livre
            </a>.
        </span>
    </div>
</footer>
