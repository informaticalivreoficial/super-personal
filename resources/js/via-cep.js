/**
 * Consulta de CEP via API pública do ViaCEP (https://viacep.com.br).
 *
 * Componente Alpine global registrado em `alpine:init` (o Livewire 3 expõe
 * window.Alpine). Uso genérico em qualquer form — basta embrulhar o grupo
 * de endereço com `<x-via-cep>` e marcar os inputs:
 *
 *   data-via-cep="zipcode"      → CEP (dispara a busca em change, com 8 dígitos)
 *   data-via-cep="street"       → logradouro
 *   data-via-cep="neighborhood" → bairro
 *   data-via-cep="city"         → cidade
 *   data-via-cep="state"        → UF (2 letras)
 *
 * Campos como número/complemento não são tocados. Após preencher, cada input
 * recebe um evento `input` (bubbles) — assim wire:model do Livewire e
 * v-model/inputs nativos atualizam o estado do form.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('viaCep', () => ({
        loading: false,

        init() {
            const cep = this.cepEl();

            if (!cep) {
                return;
            }

            // Busca ao sair do campo ou ao pressionar Enter.
            cep.addEventListener('change', () => this.lookup());
            cep.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    this.lookup();
                }
            });
        },

        cepEl() {
            return this.$el.querySelector('[data-via-cep="zipcode"]');
        },

        fieldEl(key) {
            return this.$el.querySelector(`[data-via-cep="${key}"]`);
        },

        async lookup() {
            const cep = (this.cepEl()?.value ?? '').replace(/\D/g, '');

            if (cep.length !== 8 || this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();

                if (data.erro) {
                    this.notify('CEP não encontrado. Confira o número digitado.', 'error');

                    return;
                }

                this.fill('street', data.logradouro);
                this.fill('neighborhood', data.bairro);
                this.fill('city', data.localidade);
                this.fill('state', data.uf);
            } catch {
                this.notify('Não foi possível consultar o CEP. Tente novamente.', 'error');
            } finally {
                this.loading = false;
            }
        },

        fill(key, value) {
            const el = this.fieldEl(key);

            if (!el || !value) {
                return;
            }

            el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },

        notify(message, type = 'info') {
            // Mesmo canal do toast global do painel (ToastrNotification).
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type, message },
            }));
        },
    }));
});
