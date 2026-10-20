<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitização do HTML que o painel aceita (editor Quill das páginas legais e
 * o código de incorporação do Google Maps).
 *
 * Não temos um pacote de purificação instalado e o projeto não deve ganhar
 * dependência nova sem necessidade: o allowlist abaixo cobre o que os editores
 * produzem (parágrafos, listas, títulos, links e ênfases), descarta tags
 * perigosas com o conteúdo (script/style/iframe/svg…) e valida os esquemas de
 * URL (nada de javascript:, data:, file: ou view-source:).
 */
final class HtmlSanitizer
{
    /**
     * Tags mantidas no texto rico. O valor é a lista de atributos permitidos.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'hr' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'strike' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'a' => ['href', 'title', 'target', 'rel'],
    ];

    /**
     * Tags removidas junto com o conteúdo (não adianta desembrulhar).
     *
     * @var array<int, string>
     */
    private const DANGEROUS_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'svg',
        'math',
        'template',
        'noscript',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
    ];

    /**
     * Esquemas aceitos em links. `javascript:`, `data:`, `file:` e
     * `view-source:` ficam de fora de propósito.
     *
     * @var array<int, string>
     */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Sanitiza o HTML do editor (páginas legais, textos institucionais).
     */
    public static function richText(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="sp-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sp-root');

        if (! $root) {
            return '';
        }

        // O wrapper é só uma âncora de parsing: limpar os filhos (não o div,
        // que seria desembrulhado e deixaria a lista vazia).
        foreach (iterator_to_array($root->childNodes) as $child) {
            self::cleanNode($child);
        }

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * Extrai somente o `src` de um iframe do Google Maps e devolve um embed
     * seguro (o restante do código colado pelo usuário é descartado).
     */
    public static function googleMapsEmbed(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $source = null;

        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $match)) {
            $source = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            // Aceita também a URL pura (sem o iframe ao redor).
            $candidate = trim(strip_tags($html));

            if (preg_match('~^https://~i', $candidate)) {
                $source = $candidate;
            }
        }

        if (! $source || ! self::isGoogleMapsUrl($source)) {
            return '';
        }

        return '<iframe src="'.e($source).'" width="100%" height="360" '
            .'style="border:0" loading="lazy" '
            .'referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
    }

    private static function isGoogleMapsUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! preg_match('/(^|\.)google\.com(\.br)?$/', $host)) {
            return false;
        }

        return str_starts_with((string) ($parts['path'] ?? ''), '/maps');
    }

    private static function cleanNode(DOMNode $node): void
    {
        if ($node->nodeType === XML_COMMENT_NODE) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var DOMElement $element */
        $element = $node;
        $tag = strtolower($element->tagName);

        if (in_array($tag, self::DANGEROUS_TAGS, true)) {
            $element->parentNode?->removeChild($element);

            return;
        }

        if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
            self::unwrap($element);

            return;
        }

        self::cleanAttributes($element, $tag);

        // A lista de filhos pode mudar durante a limpeza: percorrer uma cópia.
        foreach (iterator_to_array($element->childNodes) as $child) {
            self::cleanNode($child);
        }
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            $parent->insertBefore($child, $element);
            self::cleanNode($child);
        }

        $parent->removeChild($element);
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_TAGS[$tag];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        $href = $element->getAttribute('href');

        if ($href !== '' && ! self::isSafeUrl($href)) {
            $element->removeAttribute('href');
        }

        $title = $element->getAttribute('title');

        if ($title !== '') {
            $element->setAttribute('title', strip_tags($title));
        }

        if ($element->getAttribute('target') !== '_blank') {
            $element->removeAttribute('target');
        }

        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        } else {
            $element->removeAttribute('rel');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return false;
        }

        // Links internos (#ancora) não têm esquema e são seguros.
        if (str_starts_with($url, '#')) {
            return true;
        }

        // Rejeita caracteres de controle que poderiam confundir o parser.
        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, self::ALLOWED_SCHEMES, true);
    }
}
