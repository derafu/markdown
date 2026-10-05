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
            'layout in the front matter' => [
                function () {
                    $data = [];

                    return (new MarkdownService())->renderFromString("---\n__view_layout: /some/layout.php\n---\nBody", $data);
                },
                'Defining the layout with "__view_layout" in the front matter is not supported yet. Pass it in the data given to render() instead.',
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
}
