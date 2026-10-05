<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMarkdown;

use Derafu\Markdown\Contract\MarkdownCreatorInterface;
use Derafu\Markdown\Extension\Admonition\AdmonitionConfig;
use Derafu\Markdown\Extension\Admonition\AdmonitionExtension;
use Derafu\Markdown\Extension\Admonition\AdmonitionRenderer;
use Derafu\Markdown\Service\MarkdownCreator;
use Derafu\Markdown\Service\MarkdownService;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stringable;

#[CoversClass(MarkdownService::class)]
#[CoversClass(MarkdownCreator::class)]
#[UsesClass(AdmonitionExtension::class)]
#[UsesClass(AdmonitionConfig::class)]
#[UsesClass(AdmonitionRenderer::class)]
class MarkdownServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/markdown-service-' . uniqid('', true);
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    private function write(string $name, string $content): string
    {
        $file = $this->directory . '/' . $name;
        file_put_contents($file, $content);

        return $file;
    }

    public function testRenderFromStringWrapsTheContent(): void
    {
        $data = [];
        $html = (new MarkdownService())->renderFromString("Hello **world**\n", $data);

        $this->assertStringStartsWith('<div class="markdown-body">', $html);
        $this->assertStringEndsWith('</div>', $html);
        $this->assertStringContainsString('<strong>world</strong>', $html);
    }

    public function testHeadingPermalinkSymbolIsNotEscaped(): void
    {
        $data = [];
        $html = (new MarkdownService())->renderFromString("# Title\n", $data);

        $this->assertStringContainsString('<i class="fa-solid fa-link"></i>', $html);
        $this->assertStringNotContainsString('&lt;i class', $html);
    }

    public function testHeadingPermalinkIsAfterTheTextAndHasNoPrefix(): void
    {
        $data = [];
        $html = (new MarkdownService())->renderFromString("[TOC]\n\n## Same title\n\n## Same title\n", $data);

        // The IDs and the fragments are the slug, without a prefix.
        $this->assertStringContainsString('<a id="same-title" href="#same-title"', $html);
        $this->assertStringContainsString('<a id="same-title-1" href="#same-title-1"', $html);
        $this->assertStringContainsString('<li><a href="#same-title">Same title</a></li>', $html);
        $this->assertStringNotContainsString('content-same-title', $html);

        // The link goes after the text, and the heading has the class that
        // the CSS uses to show it on hover.
        $this->assertMatchesRegularExpression(
            '/<h2 class="heading-anchor">Same title<a id="same-title" href="#same-title" class="heading-permalink /',
            $html
        );
    }

    public function testConverterWithoutHeadingPermalinks(): void
    {
        $creator = new class () implements MarkdownCreatorInterface {
            public function create(array $options = []): MarkdownConverter
            {
                $environment = new Environment();
                $environment->addExtension(new CommonMarkCoreExtension());

                return new MarkdownConverter($environment);
            }
        };

        $data = [];
        $html = (new MarkdownService($creator))->renderFromString("# Title\n", $data);

        $this->assertSame('<div class="markdown-body"><h1>Title</h1>' . "\n" . '</div>', $html);
    }

    public function testFrontMatterIsMergedIntoTheData(): void
    {
        $data = ['kept' => 'yes', 'title' => 'overridden'];
        (new MarkdownService())->renderFromString("---\ntitle: From front matter\nauthor: Ana\n---\nBody\n", $data);

        $this->assertSame('From front matter', $data['title']);
        $this->assertSame('Ana', $data['author']);
        $this->assertSame('yes', $data['kept']);
    }

    public function testRenderFindsTheTemplateInThePaths(): void
    {
        $this->write('page.md', 'In md');
        $this->write('other.markdown', 'In markdown');
        $service = new MarkdownService(null, [$this->directory]);

        $data = [];
        $this->assertStringContainsString('In md', $service->render('page', $data));
        $this->assertStringContainsString('In md', $service->render('page.md', $data));
        $this->assertStringContainsString('In markdown', $service->render('other.markdown', $data));
    }

    public function testRenderAcceptsAnAbsolutePath(): void
    {
        $file = $this->write('page.md', 'Absolute');
        $this->write('notes.txt', 'Other extension');
        $service = new MarkdownService();

        $data = [];
        $this->assertStringContainsString('Absolute', $service->render($file, $data));
        // Without the extension (it used to be "does not exist").
        $this->assertStringContainsString('Absolute', $service->render(substr($file, 0, -3), $data));
        // A file that exists is used as it is, whatever its extension.
        $this->assertStringContainsString('Other extension', $service->render($this->directory . '/notes.txt', $data));
    }

    public function testPlaceholdersAreReplacedWithTheValueAsItIs(): void
    {
        $this->write('page.md', "{{ name }} / {{name}} / {{ price }} / {{ stringable }} / {{ list }}");
        $service = new MarkdownService(null, [$this->directory]);

        $data = [
            'name' => 'Ana',
            // Replacement references of preg_replace() that must stay as text.
            'price' => 'cost $1 and \1 and $0 and ${1}',
            'stringable' => new class () implements Stringable {
                public function __toString(): string
                {
                    return 'from object';
                }
            },
            'list' => ['not', 'scalar'],
        ];
        $html = $service->render('page', $data);

        $this->assertStringContainsString('Ana / Ana /', $html);
        $this->assertStringContainsString('cost $1 and \1 and $0 and ${1}', $html);
        $this->assertStringContainsString('from object', $html);
        // A value that is not scalar nor Stringable is left alone.
        $this->assertStringContainsString('{{ list }}', $html);
    }

    public function testKeysOfTheDataDoNotChangeTheLayout(): void
    {
        $this->write('page.md', 'Content');
        $layout = $this->write('layout.php', 'REAL LAYOUT');
        $other = $this->write('other.php', 'OTHER FILE');
        $service = new MarkdownService(null, [$this->directory]);

        $data = [
            '__view_layout' => $layout,
            'layout' => $other,
            '__layout' => $other,
            '__data' => ['__content' => 'x'],
        ];
        $html = $service->render('page', $data);

        $this->assertSame('REAL LAYOUT', $html);
    }

    public function testRenderWithALayout(): void
    {
        $this->write('page.md', 'Content');
        $layout = $this->write('layout.php', '<main data-title="<?= $title ?>"><?= $__content ?></main>');
        $service = new MarkdownService(null, [$this->directory]);

        $data = ['__view_layout' => $layout, 'title' => 'The title'];
        $html = $service->render('page', $data);

        $this->assertStringStartsWith('<main data-title="The title"><div class="markdown-body">', $html);
        $this->assertStringContainsString('Content', $html);
        $this->assertStringEndsWith('</div></main>', $html);
    }
}
