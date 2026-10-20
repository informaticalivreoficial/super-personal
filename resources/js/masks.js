/**
 * Máscaras de input, datepicker e medidor de força de senha — diretrives e
 * componentes Alpine globais (registrados em `alpine:init`, como o via-cep).
 *
 * IMask e flatpickr já são expostos globalmente em resources/js/app.js.
 *
 * Uso nas views:
 *   x-mask="cpf|cnpj|cep|phone|cell|document|card|cvv" → máscara IMask no input
 *       (wire:model ok: os Services/mutators limpam a formatação no save e o
 *       prefill é formatado no PHP — mesmo contrato dos campos sem máscara).
 *       A expressão pode ser dinâmica, avaliada no escopo Alpine e reaplicada
 *       quando o valor muda — ex.: `x-mask="keyType === 'cpf' ? 'cpf' : ..."`.
 *   x-datepicker                 → flatpickr pt-BR, d/m/Y, até hoje.
 *   x-data="passwordField"       → olho mostrar/ocultar + medidor de força
 *       (usar @input="value = $event.target.value" no input para o medidor).
 */
const MASK_PATTERNS = {
    cpf: '000.000.000-00',
    cnpj: '00.000.000/0000-00',
    cep: '00000-000',
    phone: '(00) 0000-0000',
    cell: '(00) 00000-0000',
    card: '0000 0000 0000 0000',
    cvv: '0000',
};

// Documento (CPF até 11 dígitos, CNPJ a partir do 12º).
const DOCUMENT_MASK = {
    mask: [{ mask: MASK_PATTERNS.cpf }, { mask: MASK_PATTERNS.cnpj }],
    dispatch(appended, dynamicMasked) {
        const digits = (dynamicMasked.value + appended).replace(/\D/g, '');

        return digits.length > 11
            ? dynamicMasked.compiledMasks[1]
            : dynamicMasked.compiledMasks[0];
    },
};

// Telefone do Pix: fixo (10 dígitos) ou celular (11), sem o +55.
const PHONE_KEY_MASK = {
    mask: [{ mask: MASK_PATTERNS.phone }, { mask: MASK_PATTERNS.cell }],
    dispatch(appended, dynamicMasked) {
        const digits = (dynamicMasked.value + appended).replace(/\D/g, '');

        return digits.length > 10
            ? dynamicMasked.compiledMasks[1]
            : dynamicMasked.compiledMasks[0];
    },
};

const MASK_ALIASES = {
    ...MASK_PATTERNS,
    document: DOCUMENT_MASK,
    'pix-phone': PHONE_KEY_MASK,
};

// Expressão literal (alias conhecido ou pattern na mão, ex.: "99.999-999")
// vs. expressão Alpine reativa (ex.: "pixKeyType === 'cpf' ? 'cpf' : ''").
const isStaticMask = (expression) => Object.prototype.hasOwnProperty.call(MASK_ALIASES, expression)
    || /^[\d\s()./\-]+$/.test(expression);

const resolveMask = (value) => {
    if (!value) {
        return null;
    }

    if (MASK_ALIASES[value]) {
        return MASK_ALIASES[value];
    }

    // Padrão escrito na mão no estilo jQuery mask (`99.999-999`): no IMask o
    // dígito é `0` — `9` seria literal e travaria a digitação. Normaliza para
    // não deixar a armadilha nas views.
    return /^[\d\s()./\-]+$/.test(value)
        ? value.replace(/9/g, '0')
        : value;
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Máscara IMask — x-mask="cpf" (ou um pattern na mão, ex.: x-mask="00").
    // Expressões reativas são reavaliadas pelo efeito do Alpine: ao trocar o
    // tipo da chave Pix, a máscara é recriada já com o texto digitado.
    Alpine.directive('mask', (el, { expression }, { effect, evaluate, cleanup }) => {
        let mask = null;
        let pattern = null;

        const apply = (raw) => {
            const next = resolveMask(raw);

            if (next === pattern) {
                return;
            }

            const value = mask ? mask.unmaskedValue : el.value;

            if (mask) {
                mask.destroy();
                mask = null;
            }

            pattern = next;

            if (next === null) {
                return;
            }

            mask = window.IMask(el, { mask: next });

            if (value) {
                mask.unmaskedValue = value;
            }
        };

        if (isStaticMask(expression)) {
            apply(expression);
        } else {
            effect(() => apply(evaluate(expression)));
        }

        cleanup(() => {
            if (mask) {
                mask.destroy();
                mask = null;
            }
        });
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
