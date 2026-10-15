/**
 * Máscaras de input, datepicker e medidor de força de senha — diretrives e
 * componentes Alpine globais (registrados em `alpine:init`, como o via-cep).
 *
 * IMask e flatpickr já são expostos globalmente em resources/js/app.js.
 *
 * Uso nas views:
 *   x-mask="cpf|cep|phone|cell"  → máscara IMask no input (wire:model ok:
 *       os mutators do User limpam a formatação no save e os accessors
 *       reformatam no prefill — mesmo contrato dos campos sem máscara).
 *   x-datepicker                 → flatpickr pt-BR, d/m/Y, até hoje.
 *   x-data="passwordField"       → olho mostrar/ocultar + medidor de força
 *       (usar @input="value = $event.target.value" no input para o medidor).
 */
const MASK_PATTERNS = {
    cpf: '000.000.000-00',
    cep: '00000-000',
    phone: '(00) 0000-0000',
    cell: '(00) 00000-0000',
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Máscara IMask — x-mask="cpf" (ou um pattern na mão, ex.: x-mask="00").
    Alpine.directive('mask', (el, { expression }) => {
        const pattern = MASK_PATTERNS[expression] || expression;
        const mask = window.IMask(el, { mask: pattern });

        return () => mask.destroy();
    });

    // Data (nascimento etc.) — flatpickr com locale pt-BR e formato d/m/Y
    // (mesmo formato do validador date_format:d/m/Y e dos mutators do User).
    Alpine.directive('datepicker', (el) => {
        const picker = window.flatpickr(el, {
            dateFormat: 'd/m/Y',
            locale: window.FlatpickrPortuguese,
            maxDate: 'today',
            allowInput: true,
            onChange() {
                // wire:model do Livewire escuta o evento input.
                el.dispatchEvent(new Event('input', { bubbles: true }));
            },
        });

        return () => picker.destroy();
    });

    // Campo de senha: olho mostrar/ocultar + medidor de força animado.
    Alpine.data('passwordField', () => ({
        show: false,
        value: '',

        /** Pontua 0..4 (com teto p/ senhas repetidas ou só dígitos). */
        get score() {
            const v = this.value;

            if (!v) {
                return 0;
            }

            if (/^(.)\1*$/.test(v) || /^\d+$/.test(v)) {
                return 1;
            }

            let points = 0;

            if (v.length >= 8) {
                points++;
            }

            if (v.length >= 12) {
                points++;
            }

            if (/[a-z]/.test(v)) {
                points++;
            }

            if (/[A-Z]/.test(v)) {
                points++;
            }

            if (/\d/.test(v)) {
                points++;
            }

            if (/[^A-Za-z0-9]/.test(v)) {
                points++;
            }

            if (points <= 2) {
                return 1;
            }

            if (points === 3) {
                return 2;
            }

            if (points === 4) {
                return 3;
            }

            return 4;
        },

        get label() {
            return ['', 'Muito fraca', 'Fraca', 'Boa', 'Forte'][this.score];
        },

        get barClass() {
            return ['', 'bg-red-500', 'bg-amber-500', 'bg-brand-500', 'bg-green-500'][this.score];
        },

        get textClass() {
            return ['', 'text-red-600', 'text-amber-600', 'text-brand-600', 'text-green-600'][this.score];
        },

        get width() {
            return `${this.score * 25}%`;
        },
    }));
});
