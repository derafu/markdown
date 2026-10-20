<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMarkdown\Extension\Admonition;

use Derafu\Markdown\Extension\Admonition\Admonition;
use Derafu\Markdown\Extension\Admonition\AdmonitionConfig;
use Derafu\Markdown\Extension\Admonition\AdmonitionContinueParser;
use Derafu\Markdown\Extension\Admonition\AdmonitionExtension;
use Derafu\Markdown\Extension\Admonition\AdmonitionRenderer;
use Derafu\Markdown\Extension\Admonition\AdmonitionStartParser;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Renderer\HtmlRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Admonition Extension.
 *
 * This extension allows creating alert-style blocks in markdown with the
 * following syntax:
 *
 *   > [!TYPE] Optional Title
 *   > Content with *markdown* support
 *
 * Features tested:
 *
 *   - Different admonition types (TIP, NOTE, WARNING, etc).
 *   - Custom and default titles.
 *   - Markdown processing in content.
 *   - Multiple paragraphs.
 *   - Complex content (lists, code, nested markdown).
 */
#[CoversClass(AdmonitionExtension::class)]
#[CoversClass(AdmonitionConfig::class)]
#[CoversClass(Admonition::class)]
#[CoversClass(AdmonitionStartParser::class)]
#[CoversClass(AdmonitionContinueParser::class)]
#[CoversClass(AdmonitionRenderer::class)]
class AdmonitionTest extends TestCase
{
    private MarkdownConverter $converter;

    protected function setUp(): void
    {
        $config = new AdmonitionConfig();
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AdmonitionExtension($config));
        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Test an admonition with a custom title and simple content.
     */
    public function testBasicAdmonitionWithCustomTitle(): void
    {
        $markdown = <<<'MARKDOWN'
> [!NOTE] Important Information
>
> This is a simple note with **bold** and *italic* text.
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-info admonition admonition-note">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-circle-info fa-fw" aria-hidden="true"></i> <strong>Important Information</strong></div>
<div class="admonition-body"><p>This is a simple note with <strong>bold</strong> and <em>italic</em> text.</p></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    /**
     * Test an admonition without a custom title (should use default).
     */
    public function testAdmonitionWithDefaultTitle(): void
    {
        $markdown = <<<'MARKDOWN'
> [!WARNING]
>
> Please be careful when using this feature.
> It might have unexpected consequences.
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-warning admonition admonition-warning">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-triangle-exclamation fa-fw" aria-hidden="true"></i> <strong>Warning</strong></div>
<div class="admonition-body"><p>Please be careful when using this feature.
It might have unexpected consequences.</p></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    /**
     * Test an admonition with multiple paragraphs and complex markdown.
     */
    public function testAdmonitionWithComplexContent(): void
    {
        $markdown = <<<'MARKDOWN'
> [!TIP] Advanced Usage
>
> Here's how to use this feature effectively:
>
> First, configure your settings properly.
> Then, try using `code` and [links](https://example.com).
>
> Remember to check the *documentation* for more **details**.
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-success admonition admonition-tip">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-lightbulb fa-fw" aria-hidden="true"></i> <strong>Advanced Usage</strong></div>
<div class="admonition-body"><p>Here's how to use this feature effectively:</p>
<p>First, configure your settings properly.
Then, try using <code>code</code> and <a href="https://example.com">links</a>.</p>
<p>Remember to check the <em>documentation</em> for more <strong>details</strong>.</p></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    /**
     * Test different admonition types and their corresponding styles.
     */
    public function testDifferentAdmonitionTypes(): void
    {
        $types = [
            'note' => 'alert-info',
            'warning' => 'alert-warning',
            'danger' => 'alert-danger',
            'success' => 'alert-success',
            'example' => 'alert-secondary',
        ];

        foreach ($types as $type => $expectedClass) {
            $markdown = <<<MARKDOWN
> [!{$type}] Test
> Content
MARKDOWN;

            $html = $this->converter->convert($markdown)->getContent();
            $this->assertStringContainsString($expectedClass, $html, "Admonition type '{$type}' should use class '{$expectedClass}'");
            $this->assertStringContainsString('<p>Content</p>', $html, "Admonition type '{$type}' should keep its content");
        }
    }

    /**
     * Test an admonition with inline code.
     */
    public function testAdmonitionWithInlineCode(): void
    {
        $markdown = <<<'MARKDOWN'
> [!EXAMPLE] Code Sample
>
> You can use `inline code` like this.
> And also use `multiple` pieces of `code`.
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-secondary admonition admonition-example">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-list fa-fw" aria-hidden="true"></i> <strong>Code Sample</strong></div>
<div class="admonition-body"><p>You can use <code>inline code</code> like this.
And also use <code>multiple</code> pieces of <code>code</code>.</p></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    /**
     * Test an admonition with nested elements.
     */
    public function testAdmonitionWithNestedElements(): void
    {
        $markdown = <<<'MARKDOWN'
> [!IMPORTANT] Complex Example
>
> ### Section Title
>
> Here's a **complex** example with:
> - *Italic* text in a list
> - A [link](https://example.com) in a list
> - Some `code` in a list
>
> And a final paragraph with **bold** text.
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-warning admonition admonition-important">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-circle-exclamation fa-fw" aria-hidden="true"></i> <strong>Complex Example</strong></div>
<div class="admonition-body"><h3>Section Title</h3>
<p>Here's a <strong>complex</strong> example with:</p>
<ul>
<li><em>Italic</em> text in a list</li>
<li>A <a href="https://example.com">link</a> in a list</li>
<li>Some <code>code</code> in a list</li>
</ul>
<p>And a final paragraph with <strong>bold</strong> text.</p></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    /**
     * Test an admonition with lists (ordered and unordered).
     */
    public function testAdmonitionWithLists(): void
    {
        $markdown = <<<'MARKDOWN'
> [!NOTE] Lists Example
>
> Ordered list:
>
> 1. First item
> 2. Second item
>
> Unordered list:
>
> * Apple
> * Banana
> * Orange
MARKDOWN;

        $expected = <<<'HTML'
<div class="alert alert-info admonition admonition-note">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-circle-info fa-fw" aria-hidden="true"></i> <strong>Lists Example</strong></div>
<div class="admonition-body"><p>Ordered list:</p>
<ol>
<li>First item</li>
<li>Second item</li>
</ol>
<p>Unordered list:</p>
<ul>
<li>Apple</li>
<li>Banana</li>
<li>Orange</li>
</ul></div>
</div>
HTML;

        $this->assertSame(
            $this->normalizeHtml($expected),
            $this->normalizeHtml($this->converter->convert($markdown)->getContent())
        );
    }

    public function testAMarkerThatIsIndentedIsACodeBlock(): void
    {
        $html = $this->converter->convert("    > [!NOTE]\n    > text\n")->getContent();

        $this->assertStringContainsString('<pre><code>&gt; [!NOTE]', $html);
        $this->assertStringNotContainsString('admonition', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notMarkers(): array
    {
        return [
            'without the type' => ["> [!]\n> text\n"],
            'without the closing bracket' => ["> [!NOTE\n> text\n"],
            'a quote' => ["> just a quote\n"],
        ];
    }

    #[DataProvider('notMarkers')]
    public function testWhatIsNotAMarkerStaysAsABlockquote(string $markdown): void
    {
        $html = $this->converter->convert($markdown)->getContent();

        $this->assertStringStartsWith('<blockquote>', $html);
        $this->assertStringNotContainsString('admonition', $html);
    }

    public function testAnAdmonitionEndsWhenTheQuoteEnds(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n>\n> Inside.\n\nOutside.\n")->getContent();

        $this->assertStringContainsString('<div class="admonition-body"><p>Inside.</p></div>', $html);
        $this->assertStringEndsWith("</div>\n<p>Outside.</p>\n", $html);
    }

    public function testAnAdmonitionBuiltByOtherCodeRendersItsChildrenWithTheDefaultTitle(): void
    {
        // The nodes can be built by other code, not only by the parser.
        $document = new Document();
        $admonition = new Admonition('tip');
        $admonition->appendChild(new ThematicBreak());
        $document->appendChild($admonition);
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AdmonitionExtension());

        $html = (new HtmlRenderer($environment))->renderDocument($document)->getContent();

        $this->assertStringContainsString('class="alert alert-success admonition admonition-tip"', $html);
        $this->assertStringContainsString('<strong>Tip</strong>', $html);
        $this->assertStringContainsString('<div class="admonition-body"><hr /></div>', $html);
    }

    /**
     * The content can come right after the marker, as in GitHub, or after an
     * empty line: the HTML is the same.
     *
     * @return array<string, array{string, string}>
     */
    public static function withAndWithoutTheEmptyLine(): array
    {
        return [
            'a line' => ["> [!NOTE]\n> Text.\n", "> [!NOTE]\n>\n> Text.\n"],
            'two lines' => ["> [!NOTE]\n> One.\n> Two.\n", "> [!NOTE]\n>\n> One.\n> Two.\n"],
            'a title' => ["> [!TIP] The title\n> Text.\n", "> [!TIP] The title\n>\n> Text.\n"],
            'two paragraphs' => ["> [!NOTE]\n> One.\n>\n> Two.\n", "> [!NOTE]\n>\n> One.\n>\n> Two.\n"],
            'a list' => ["> [!NOTE]\n> - a\n> - b\n", "> [!NOTE]\n>\n> - a\n> - b\n"],
            'code' => ["> [!NOTE]\n> ```php\n> echo 1;\n> ```\n", "> [!NOTE]\n>\n> ```php\n> echo 1;\n> ```\n"],
            'a heading' => ["> [!NOTE]\n> ## Section\n> Text.\n", "> [!NOTE]\n>\n> ## Section\n> Text.\n"],
        ];
    }

    #[DataProvider('withAndWithoutTheEmptyLine')]
    public function testTheEmptyLineAfterTheMarkerDoesNotChangeTheHtml(string $without, string $with): void
    {
        $html = $this->converter->convert($without)->getContent();

        $this->assertSame($html, $this->converter->convert($with)->getContent());
        // Nothing between the title and the content: no empty paragraph.
        $this->assertStringNotContainsString('<p></p>', $html);
        $this->assertStringContainsString('</div>' . "\n" . '<div class="admonition-body"><', $html);
    }

    public function testTheContentRightAfterTheMarkerIsTheBody(): void
    {
        $html = $this->converter->convert("> [!WARNING]\n> First line.\n> Second **line**.\n")->getContent();

        $this->assertSame(
            $this->normalizeHtml(<<<'HTML'
<div class="alert alert-warning admonition admonition-warning">
<div class="admonition-title lead mb-2 text-uppercase"><i class="fa-solid fa-triangle-exclamation fa-fw" aria-hidden="true"></i> <strong>Warning</strong></div>
<div class="admonition-body"><p>First line.
Second <strong>line</strong>.</p></div>
</div>
HTML),
            $this->normalizeHtml($html)
        );
    }

    public function testTheTitleOfTheMarkerLineIsEscaped(): void
    {
        $html = $this->converter->convert("> [!NOTE] A <b>bold</b> & more\n> Text.\n")->getContent();

        $this->assertStringContainsString('<strong>A &lt;b&gt;bold&lt;/b&gt; &amp; more</strong>', $html);
    }

    public function testAQuoteInsideTheAdmonitionIsAQuote(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n> Text.\n> > Quoted.\n")->getContent();

        $this->assertStringContainsString('<div class="admonition-body"><p>Text.</p>' . "\n" . '<blockquote>', $html);
        $this->assertStringContainsString('<p>Quoted.</p>', $html);
    }

    public function testALineThatContinuesTheParagraphWithoutTheMarkerStaysInTheAdmonition(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n> Text\ncontinues here.\n")->getContent();

        $this->assertStringContainsString("<p>Text\ncontinues here.</p>", $html);
    }

    public function testASecondMarkerInTheContentIsPlainText(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n> [!TIP] Not a title.\n")->getContent();

        $this->assertStringContainsString('<strong>Note</strong>', $html);
        $this->assertStringContainsString('<p>[!TIP] Not a title.</p>', $html);
    }

    public function testAnAdmonitionCanBeInsideAListItemOrAQuote(): void
    {
        $inList = $this->converter->convert("- Item\n\n  > [!TIP]\n  > Inside the item.\n")->getContent();
        $inQuote = $this->converter->convert("> > [!TIP]\n> > Inside the quote.\n")->getContent();

        $this->assertStringContainsString('admonition-tip', $inList);
        $this->assertStringContainsString('<p>Inside the item.</p>', $inList);
        $this->assertStringContainsString('<blockquote>', $inQuote);
        $this->assertStringContainsString('admonition-tip', $inQuote);
        $this->assertStringContainsString('<p>Inside the quote.</p>', $inQuote);
    }

    public function testTwoAdmonitionsOneAfterTheOtherAreSeparate(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n> One.\n\n> [!TIP]\n> Two.\n")->getContent();

        $this->assertSame(1, substr_count($html, 'admonition-note'));
        $this->assertSame(1, substr_count($html, 'admonition-tip'));
        $this->assertStringContainsString('<p>One.</p></div></div>', $html);
        $this->assertStringContainsString('<p>Two.</p></div></div>', $html);
    }

    public function testAnAdmonitionWithOnlyTheMarkerHasAnEmptyBody(): void
    {
        $html = $this->converter->convert("> [!NOTE]\n")->getContent();

        $this->assertStringContainsString('<div class="admonition-body"></div>', $html);
    }

    public function testAMarkerWithoutASpaceAfterTheQuoteIsAMarker(): void
    {
        $html = $this->converter->convert(">[!NOTE]\n>Text.\n")->getContent();

        $this->assertStringContainsString('admonition-note', $html);
        $this->assertStringContainsString('<p>Text.</p>', $html);
    }

    private function normalizeHtml(string $html): string
    {
        // Remove whitespace and line breaks.
        return preg_replace('/\s+/', '', trim($html)) ?? '';
    }
}
