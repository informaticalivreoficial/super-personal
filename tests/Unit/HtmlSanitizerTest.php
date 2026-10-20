<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_rich_text_keeps_allowed_tags_and_removes_scripts(): void
    {
        $html = '<h2>Título</h2><p>Texto <strong>forte</strong><script>alert(1)</script></p><ul><li>item</li></ul><hr>';

        $clean = HtmlSanitizer::richText($html);

        $this->assertStringContainsString('<h2>Título</h2>', $clean);
        $this->assertStringContainsString('<strong>forte</strong>', $clean);
        $this->assertStringContainsString('<ul><li>item</li></ul>', $clean);
        $this->assertStringContainsString('<hr>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
    }

    public function test_rich_text_removes_event_handlers_and_dangerous_schemes(): void
    {
        $html = '<p onclick="alert(1)">ok</p>'
            .'<a href="javascript:alert(2)">js</a>'
            .'<a href="data:text/html;base64,PHNjcmlwdD4=">data</a>'
            .'<a href="https://safe.test" target="_blank">safe</a>';

        $clean = HtmlSanitizer::richText($html);

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('data:text/html', $clean);
        $this->assertStringContainsString('href="https://safe.test"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    public function test_rich_text_removes_dangerous_tags_with_their_content(): void
    {
        $clean = HtmlSanitizer::richText('<p>antes</p><iframe src="https://x.test"></iframe><style>p{}</style><p>depois</p>');

        $this->assertStringContainsString('antes', $clean);
        $this->assertStringContainsString('depois', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('<style', $clean);
    }

    public function test_rich_text_returns_empty_for_blank_input(): void
    {
        $this->assertSame('', HtmlSanitizer::richText(null));
        $this->assertSame('', HtmlSanitizer::richText(''));
    }

    public function test_google_maps_embed_accepts_only_google_maps(): void
    {
        $clean = HtmlSanitizer::googleMapsEmbed(
            '<iframe src="https://www.google.com/maps/embed?pb=abc" width="600"></iframe>'
        );

        $this->assertStringContainsString('<iframe', $clean);
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=abc', $clean);
        $this->assertStringContainsString('loading="lazy"', $clean);

        $fromUrl = HtmlSanitizer::googleMapsEmbed('https://www.google.com/maps/embed?pb=xyz');
        $this->assertStringContainsString('src="https://www.google.com/maps/embed?pb=xyz"', $fromUrl);
    }

    public function test_google_maps_embed_rejects_other_hosts_and_schemes(): void
    {
        $this->assertSame('', HtmlSanitizer::googleMapsEmbed('https://evil.test/maps/embed?pb=abc'));
        $this->assertSame('', HtmlSanitizer::googleMapsEmbed('https://google.com.evil.test/maps/embed?pb=abc'));
        $this->assertSame('', HtmlSanitizer::googleMapsEmbed('http://www.google.com/maps/embed?pb=abc'));
        $this->assertSame('', HtmlSanitizer::googleMapsEmbed('<iframe src="javascript:alert(1)"></iframe>'));
    }
}
