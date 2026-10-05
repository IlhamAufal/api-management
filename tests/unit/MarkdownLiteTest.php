<?php

use App\Libraries\MarkdownLite;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test parser MarkdownLite (subset GFM untuk panel tutorial
 * di form tambah database). Fokus: escape aman, struktur yang
 * dipakai docs/TUTORIAL-TAMBAH-DATABASE.md.
 *
 * @internal
 */
final class MarkdownLiteTest extends CIUnitTestCase
{
    public function testParsesHeadingsParagraphBoldAndInlineCode(): void
    {
        $html = MarkdownLite::toHtml("# Judul\n\nParagraf dengan **tebal** dan `kode`.");

        $this->assertStringContainsString('<h1 class="tutorial-h">Judul</h1>', $html);
        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringContainsString('<code class="tutorial-code">kode</code>', $html);
    }

    public function testCodeFenceEscapesHtmlButKeepsText(): void
    {
        $html = MarkdownLite::toHtml("```php\necho '<b>x</b>';\n```");

        $this->assertStringContainsString('language-php', $html);
        $this->assertStringContainsString('echo &#039;&lt;b&gt;x&lt;/b&gt;&#039;;', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
    }

    public function testParsesGfmTable(): void
    {
        $html = MarkdownLite::toHtml("| A | B |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('<th>A</th>', $html);
        $this->assertStringContainsString('<th>B</th>', $html);
        $this->assertStringContainsString('<td>1</td>', $html);
        $this->assertStringContainsString('<td>2</td>', $html);
    }

    public function testParsesOrderedUnorderedAndChecklist(): void
    {
        $md   = "1. Langkah satu\n2. Langkah dua\n\n- item biasa\n- [ ] belum\n- [x] selesai";
        $html = MarkdownLite::toHtml($md);

        $this->assertStringContainsString('<ol class="tutorial-list">', $html);
        $this->assertStringContainsString('<li>Langkah satu</li>', $html);
        $this->assertStringContainsString('<ul class="tutorial-list">', $html);
        $this->assertStringContainsString('<li>item biasa</li>', $html);
        $this->assertStringContainsString('<input type="checkbox" disabled />', $html);
        $this->assertStringContainsString('<input type="checkbox" disabled checked />', $html);
    }

    public function testEscapesRawHtmlAndRejectsUnsafeLink(): void
    {
        $html = MarkdownLite::toHtml(
            "Teks <script>alert(1)</script>\n\n[jahat](javascript:alert(1)) dan [aman](https://example.com)"
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('jahat</a>', $html);
        $this->assertStringContainsString('<a class="tutorial-link" href="https://example.com">aman</a>', $html);
    }

    public function testParsesBlockquoteAndHorizontalRule(): void
    {
        $html = MarkdownLite::toHtml("> Kutipan **penting**\n\n---");

        $this->assertStringContainsString('<blockquote class="tutorial-quote">', $html);
        $this->assertStringContainsString('<strong>penting</strong>', $html);
        $this->assertStringContainsString('<hr class="tutorial-hr" />', $html);
    }
}
