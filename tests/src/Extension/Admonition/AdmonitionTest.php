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
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
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

    private function normalizeHtml(string $html): string
    {
        // Remove whitespace and line breaks.
        return preg_replace('/\s+/', '', trim($html));
    }
}
