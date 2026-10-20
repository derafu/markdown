<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMarkdown\Exception;

use Closure;
use Derafu\Markdown\Contract\MarkdownCreatorInterface;
use Derafu\Markdown\Extension\Admonition\AdmonitionConfig;
use Derafu\Markdown\Extension\Admonition\AdmonitionExtension;
use Derafu\Markdown\Extension\Admonition\AdmonitionRenderer;
use Derafu\Markdown\Service\MarkdownCreator;
use Derafu\Markdown\Service\MarkdownService;
use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Core\TranslatableRuntimeException;
use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The errors of the package are translatable and say what they always did.
 */
#[CoversClass(MarkdownService::class)]
#[CoversClass(AdmonitionConfig::class)]
#[CoversClass(AdmonitionRenderer::class)]
#[UsesClass(MarkdownCreator::class)]
#[UsesClass(AdmonitionExtension::class)]
final class MarkdownExceptionsTest extends TestCase
{
    private string $directory;

    /**
     * A creator of converters that does not need an HTTP client, which the
     * default one needs to embed media.
     */
    private function creator(): MarkdownCreatorInterface
    {
        return new class () implements MarkdownCreatorInterface {
            /**
             * @param array<string, mixed> $options
             */
            public function create(array $options = []): MarkdownConverter
            {
                $environment = new Environment(['heading_permalink' => ['symbol' => '#']]);
                $environment->addExtension(new CommonMarkCoreExtension());
                $environment->addExtension(new HeadingPermalinkExtension());

                return new MarkdownConverter($environment);
            }
        };
    }

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/markdown-exceptions-' . uniqid('', true);
        mkdir($this->directory);
        file_put_contents($this->directory . '/page.md', '# Title');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    /**
     * @return array<string, array{Closure(self): mixed, string}>
     */
    public static function failuresProvider(): array
    {
        return [
            'template that does not exist' => [
                fn (self $test) => (new MarkdownService($test->creator()))->render('missing.md'),
                'Template missing.md does not exists.',
            ],
            'layout that is not an absolute path' => [
                function (self $test) {
                    $data = ['__view_layout' => 'layout.php'];

                    return (new MarkdownService($test->creator(), [$test->directory]))->render('page.md', $data);
                },
                'Invalid layout path: layout.php. It must be an absolute path.',
            ],
            'layout that is not a string' => [
                function (self $test) {
                    $data = ['__view_layout' => ['layout.php']];

                    return (new MarkdownService($test->creator(), [$test->directory]))->render('page.md', $data);
                },
                'Invalid layout path: array. It must be an absolute path.',
            ],
            'layout in the front matter' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\n__view_layout: /some/layout.php\n---\nBody", $data);
                },
                'Defining the layout with "__view_layout" in the front matter is not supported yet. Pass it in the data given to render() instead.',
            ],
            'front matter that is a string' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\njust a string\n---\nBody", $data);
                },
                'The front matter must be a map of names to values; string given.',
            ],
            'front matter that is a number' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\n42\n---\nBody", $data);
                },
                'The front matter must be a map of names to values; int given.',
            ],
            'front matter that is a list' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\n- one\n- two\n---\nBody", $data);
                },
                'The front matter must be a map of names to values; the key 0 is not a name.',
            ],
            'front matter with numbers as keys' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\n1: one\n2: two\n---\nBody", $data);
                },
                'The front matter must be a map of names to values; the key 1 is not a name.',
            ],
            'admonition type without a key' => [
                fn (self $test) => (new AdmonitionConfig())->addType('custom', []),
                'Missing required key: bootstrap_class',
            ],
            'node that is not an admonition' => [
                fn (self $test) => (new AdmonitionRenderer())->render(
                    new Paragraph(),
                    $test->createStub(ChildNodeRendererInterface::class)
                ),
                'Invalid node type: League\CommonMark\Node\Block\Paragraph',
            ],
        ];
    }

    /**
     * @param Closure(self): mixed $action
     */
    #[DataProvider('failuresProvider')]
    public function testEveryFailureIsATranslatableErrorThatSaysTheSame(Closure $action, string $message): void
    {
        $exception = null;
        try {
            $action($this);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testATemplateThatCanNotBeReadIsReported(): void
    {
        $file = $this->directory . '/secret.md';
        file_put_contents($file, '# Secret');
        chmod($file, 0);
        if (is_readable($file)) {
            $this->markTestSkipped('The permissions of the file are not enforced (root).');
        }

        // PHP also warns that it could not open the file.
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            (new MarkdownService($this->creator(), [$this->directory]))->render('secret.md');
            $this->fail('A template that can not be read must be reported.');
        } catch (TranslatableRuntimeException $exception) {
            $this->assertSame('Template ' . $file . ' could not be read.', $exception->getMessage());
        } finally {
            restore_error_handler();
            chmod($file, 0644);
        }
    }

    public function testATemplateThatTheRegularExpressionCanNotProcessIsReported(): void
    {
        file_put_contents($this->directory . '/greeting.md', "# {{ title }}\n\nSome text so the expression has to look for the placeholder.\n");

        $limits = $this->limitTheRegularExpressions();
        try {
            $data = ['title' => 'Hello'];
            (new MarkdownService($this->creator(), [$this->directory]))->render('greeting.md', $data);
            $this->fail('A template that can not be processed must be reported.');
        } catch (TranslatableRuntimeException $exception) {
            $this->assertStringStartsWith('The template could not be processed: ', $exception->getMessage());
        } finally {
            $this->restoreTheRegularExpressions($limits);
        }
    }

    /**
     * Makes the regular expressions fail when they have to backtrack, with an
     * error of PCRE (`preg_*` functions return `null`).
     *
     * @return array{string|false, string|false}
     */
    private function limitTheRegularExpressions(): array
    {
        return [ini_set('pcre.jit', '0'), ini_set('pcre.backtrack_limit', '1')];
    }

    /**
     * @param array{string|false, string|false} $limits
     */
    private function restoreTheRegularExpressions(array $limits): void
    {
        ini_set('pcre.jit', (string) $limits[0]);
        ini_set('pcre.backtrack_limit', (string) $limits[1]);
    }
}
