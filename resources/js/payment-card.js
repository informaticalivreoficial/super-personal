/**
 * Pagamento com cartão de crédito (checkout transparente) — componente Alpine
 * global `x-data="cardPayment({ publicKey, amount, cpfId })"`.
 *
 * O dado sensível NUNCA sai do navegador em texto: o SDK do parceiro de
 * pagamentos criptografa número/validade/CVV com a chave pública da conta e
 * só o hash (`encryptedCard`) é enviado ao Livewire (`payWithCard`).
 *
 * O SDK é carregado sob demanda (a tag `@assets` não existe aqui): cobre
 * navegação SPA do Livewire, CDN lenta ou falha de load.
 */
const SDK_SRC = 'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js';

/** Garante o SDK no navegador (reaproveita a tag existente; timeout de 15s). */
const ensureSdk = () => {
    if (window.PagSeguro) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
            reject(new Error('O módulo de pagamento demorou para carregar. Verifique sua conexão e tente novamente.'));
        }, 15000);

        const done = () => {
            clearTimeout(timeout);
            resolve();
        };

        const fail = () => {
            clearTimeout(timeout);
            reject(new Error('Não foi possível carregar o módulo de pagamento. Verifique sua conexão e tente novamente.'));
        };

        const existing = document.querySelector('script[src="' + SDK_SRC + '"]');

        if (existing) {
            if (window.PagSeguro) {
                return done();
            }

            existing.addEventListener('load', done, { once: true });
            existing.addEventListener('error', fail, { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = SDK_SRC;
        script.async = true;
        script.onload = done;
        script.onerror = () => {
            script.remove();
            fail();
        };
        document.head.appendChild(script);
    });
};

/** Mensagens dos erros locais de criptografia (códigos do SDK). */
const ENCRYPT_ERRORS = {
    INVALID_NUMBER: 'Número do cartão inválido.',
    INVALID_SECURITY_CODE: 'Código de segurança (CVV) inválido.',
    INVALID_EXPIRATION_MONTH: 'Mês de validade inválido.',
    INVALID_EXPIRATION_YEAR: 'Ano de validade inválido.',
    INVALID_HOLDER: 'Informe o nome impresso no cartão.',
    INVALID_PUBLIC_KEY: 'Não foi possível preparar o pagamento. Recarregue a página e tente novamente.',
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('cardPayment', (config = {}) => ({
        number: '',
        holder: '',
        month: '',
        year: '',
        cvv: '',
        installments: 1,
        processing: false,
        error: '',

        init() {
            const now = new Date();
            this.months = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
            this.years = Array.from({ length: 12 }, (_, i) => String(now.getFullYear() + i));
        },

        get total() {
            return Number(config.amount) || 0;
        },

        get installmentOptions() {
            return Array.from({ length: 12 }, (_, i) => {
                const n = i + 1;
                const value = (this.total / n).toFixed(2).replace('.', ',');

                return { n, label: n === 1 ? `1x de R$ ${value} (à vista)` : `${n}x de R$ ${value}` };
            });
        },

        get canPay() {
            return !this.processing
                && this.number.replace(/\D/g, '').length >= 13
                && this.holder.trim().length >= 3
                && this.month !== ''
                && this.year !== ''
                && this.cvv.length >= 3;
        },

        async pay() {
            this.error = '';

            if (!this.canPay) {
                this.error = 'Confira os dados do cartão para continuar.';

                return;
            }

            this.processing = true;

            try {
                await ensureSdk();

                const card = window.PagSeguro.encryptCard({
                    publicKey: config.publicKey,
                    holder: this.holder.trim(),
                    number: this.number.replace(/\D/g, ''),
                    expMonth: this.month,
                    expYear: this.year,
                    securityCode: this.cvv,
                });

                if (card.hasErrors) {
                    const first = (card.errors && card.errors[0]) || {};
                    this.error = ENCRYPT_ERRORS[first.code] || 'Não foi possível validar os dados do cartão.';

                    return;
                }

                const cpfInput = document.getElementById(config.cpfId || 'pay-cpf');

                const result = await this.$wire.call('payWithCard', {
                    encrypted: card.encryptedCard,
                    holder: this.holder.trim(),
                    installments: Number(this.installments),
                    cpf: (cpfInput ? cpfInput.value : '').replace(/\D/g, ''),
                });

                // Recusa/erro: mantém o formulário aberto para nova tentativa
                // (o toast detalhado já vem do servidor).
                if (result && result.status !== 'approved' && result.status !== 'pending') {
                    this.error = (result && result.message) || 'Pagamento não aprovado. Confira os dados e tente novamente.';
                }
            } catch (e) {
                this.error = (e && e.message) || 'Não foi possível processar o pagamento.';
            } finally {
                this.processing = false;
            }
        },
    }));
});
