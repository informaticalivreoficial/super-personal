import './bootstrap';

import flatpickr from "flatpickr"
import { Portuguese } from "flatpickr/dist/l10n/pt.js"

window.flatpickr = flatpickr
window.FlatpickrPortuguese = Portuguese

import Swal from 'sweetalert2'
window.Swal = Swal

import IMask from 'imask';
window.IMask = IMask;

// Consulta de CEP (ViaCEP) — componente Alpine global x-data="viaCep".
import './via-cep';

// Máscaras (IMask), datepicker (flatpickr) e força de senha — x-mask,
// x-datepicker e x-data="passwordField".
import './masks';

// Checkout transparente: criptografia do cartão no navegador —
// x-data="cardPayment({ publicKey, amount, cpfId })".
import './payment-card';
