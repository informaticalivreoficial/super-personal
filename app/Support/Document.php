<?php

namespace App\Support;

/**
 * Formatação de CPF/CNPJ para exibição e edição.
 *
 * O banco guarda o documento só com dígitos (formato enviado aos gateways);
 * a formatação é aplicada na hora de mostrar no formulário/lista.
 */
class Document
{
    public static function format(?string $document): ?string
    {
        if ($document === null || trim($document) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $document) ?? '';

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits),
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits),
            default => $document,
        };
    }
}
