<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMarkdown;

use Derafu\Markdown\Extension\Admonition\AdmonitionExtension;
use Derafu\Markdown\Extension\Admonition\AdmonitionRenderer;
use Derafu\Markdown\Extension\Admonition\AdmonitionStartParser;
use Derafu\Markdown\Service\MarkdownCreator;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Embed\Bridge\OscaroteroEmbedAdapter;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * An extension that is not one of the default ones.
 */
final class CreatorTestExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
    }
}

#[CoversClass(MarkdownCreator::class)]
#[UsesClass(AdmonitionExtension::class)]
#[UsesClass(AdmonitionRenderer::class)]
#[UsesClass(AdmonitionStartParser::class)]
final class MarkdownCreatorTest extends TestCase
{
    private ?string $host = null;

    protected function setUp(): void
    {
        $this->host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
            ? $_SERVER['HTTP_HOST']
            : null;
        unset($_SERVER['HTTP_HOST']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
        if ($this->host !== null) {
            $_SERVER['HTTP_HOST'] = $this->host;
        }
    }

    /**
     * Tells if a link of the HTML is an external link.
     */
    private function isExternal(string $html, string $url): bool
    {
        if (preg_match('/<a [^>]*href="' . preg_quote($url, '/') . '"[^>]*>/', $html, $link) !== 1) {
            $this->fail('Link not found: ' . $url);
        }

        return str_contains($link[0], 'external-link');
    }

    /**
     * @return list<class-string>
     */
    private function extensionsOf(MarkdownConverter $converter): array
    {
        $classes = [];
        foreach ($converter->getEnvironment()->getExtensions() as $extension) {
            $classes[] = $extension::class;
        }

        return $classes;
    }

    #[Test]
    public function theDefaultConverterHasTheDefaultExtensions(): void
    {
        $extensions = $this->extensionsOf((new MarkdownCreator())->create());

        $this->assertContains(CommonMarkCoreExtension::class, $extensions);
        $this->assertContains(AttributesExtension::class, $extensions);
        $this->assertContains(AdmonitionExtension::class, $extensions);
    }

    #[Test]
    public function theExtensionsThatAreGivenAreAddedToTheDefaultOnes(): void
    {
        $default = $this->extensionsOf((new MarkdownCreator())->create());

        $extensions = $this->extensionsOf(
            (new MarkdownCreator(['extensions' => [CreatorTestExtension::class]]))->create()
        );

        $this->assertContains(CreatorTestExtension::class, $extensions);
        // None of the default ones is lost, as `AttributesExtension` was when
        // the list replaced the first positions of the default one.
        $this->assertSame([], array_values(array_diff($default, $extensions)));
        $this->assertCount(count($default) + 1, $extensions);
    }

    #[Test]
    public function anExtensionThatIsAlreadyThereIsNotRepeated(): void
    {
        $default = $this->extensionsOf((new MarkdownCreator())->create());

        $extensions = $this->extensionsOf(
            (new MarkdownCreator(['extensions' => [CommonMarkCoreExtension::class, CommonMarkCoreExtension::class]]))->create()
        );

        $this->assertCount(count($default), $extensions);
    }

    #[Test]
    public function theExtensionsOfTheConstructorAndOfCreateAreBothUsed(): void
    {
        $creator = new MarkdownCreator(['extensions' => [CreatorTestExtension::class]]);

        $extensions = $this->extensionsOf($creator->create());

        $this->assertContains(CreatorTestExtension::class, $extensions);
        $this->assertContains(AdmonitionExtension::class, $extensions);
    }

    #[Test]
    public function theEnvironmentIsMergedRecursivelyOverTheDefaultOne(): void
    {
        $converter = (new MarkdownCreator(['environment' => ['heading_permalink' => ['symbol' => '#']]]))->create();

        $html = $converter->convert("# Title\n")->getContent();

        // The symbol is the one given, and the rest of the options of the
        // permalinks are still the default ones.
        $this->assertStringContainsString('class="heading-permalink text-decoration-none small text-muted"', $html);
        $this->assertStringContainsString('#</a>', $html);
        $this->assertStringNotContainsString('fa-link', $html);
    }

    #[Test]
    public function theEmbedLibraryBecomesTheAdapterOfTheEmbedExtension(): void
    {
        $configuration = (new MarkdownCreator())->create()->getEnvironment()->getConfiguration();

        $this->assertInstanceOf(OscaroteroEmbedAdapter::class, $configuration->get('embed/adapter'));
        $this->assertFalse($configuration->exists('embed/library'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidOptions(): array
    {
        return [
            'extensions that is not an array' => [
                ['extensions' => 'Some\\Extension'],
                'The option extensions must be an array; string given.',
            ],
            'an extension that is not a string' => [
                ['extensions' => [123]],
                'The extension int must be a class that implements ' . ExtensionInterface::class . '.',
            ],
            'an extension that is not a class' => [
                ['extensions' => ['NoSuchClass']],
                'The extension NoSuchClass must be a class that implements ' . ExtensionInterface::class . '.',
            ],
            'an extension that is not an extension' => [
                ['extensions' => [stdClass::class]],
                'The extension stdClass must be a class that implements ' . ExtensionInterface::class . '.',
            ],
            'environment that is not an array' => [
                ['environment' => 'table_of_contents'],
                'The option environment must be an array; string given.',
            ],
            'environment with a key that is not a name' => [
                ['environment' => [0 => 'value']],
                'The keys of the option environment must be names; 0 given.',
            ],
            'embed library that is not an array' => [
                ['environment' => ['embed' => ['library' => 'maxwidth']]],
                'The option environment.embed.library must be an array; string given.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Test]
    #[DataProvider('invalidOptions')]
    public function optionsThatAreNotValidAreReportedWhenTheCreatorIsCreated(array $options, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new MarkdownCreator($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Test]
    #[DataProvider('invalidOptions')]
    public function optionsThatAreNotValidAreReportedWhenTheConverterIsCreated(array $options, string $message): void
    {
        $creator = new MarkdownCreator();

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $creator->create($options);
    }

    #[Test]
    public function theHostOfTheRequestIsTheInternalHost(): void
    {
        $_SERVER['HTTP_HOST'] = 'site.example.com';

        $html = (new MarkdownCreator())->create()
            ->convert("[a](https://site.example.com/page) [b](https://other.example.com/page)\n")->getContent();

        $this->assertFalse($this->isExternal($html, 'https://site.example.com/page'));
        $this->assertTrue($this->isExternal($html, 'https://other.example.com/page'));
    }

    #[Test]
    public function withoutARequestAllTheLinksAreExternal(): void
    {
        $html = (new MarkdownCreator())->create()
            ->convert("[a](https://site.example.com/page)\n")->getContent();

        $this->assertTrue($this->isExternal($html, 'https://site.example.com/page'));
    }

    #[Test]
    public function thePortOfTheHostOfTheRequestIsNotPartOfTheInternalHost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $html = (new MarkdownCreator())->create()
            ->convert("[a](http://localhost:8080/page)\n")->getContent();

        $this->assertFalse($this->isExternal($html, 'http://localhost:8080/page'));
    }

    #[Test]
    public function theInternalHostsOfTheConstructorWinOverTheHostOfTheRequest(): void
    {
        $_SERVER['HTTP_HOST'] = 'request.example.com';
        $creator = new MarkdownCreator(['environment' => ['external_link' => ['internal_hosts' => ['mine.example.com']]]]);

        $html = $creator->create()
            ->convert("[a](https://mine.example.com/) [b](https://request.example.com/)\n")->getContent();

        $this->assertFalse($this->isExternal($html, 'https://mine.example.com/'));
        $this->assertTrue($this->isExternal($html, 'https://request.example.com/'));
    }

    #[Test]
    public function theInternalHostsOfCreateWinOverTheHostOfTheRequest(): void
    {
        $_SERVER['HTTP_HOST'] = 'request.example.com';

        $html = (new MarkdownCreator())
            ->create(['environment' => ['external_link' => ['internal_hosts' => ['mine.example.com']]]])
            ->convert("[a](https://mine.example.com/) [b](https://request.example.com/)\n")->getContent();

        $this->assertFalse($this->isExternal($html, 'https://mine.example.com/'));
        $this->assertTrue($this->isExternal($html, 'https://request.example.com/'));
    }

    #[Test]
    public function anEmptyListOfInternalHostsMeansThatThereAreNone(): void
    {
        $_SERVER['HTTP_HOST'] = 'request.example.com';

        $html = (new MarkdownCreator(['environment' => ['external_link' => ['internal_hosts' => []]]]))->create()
            ->convert("[a](https://request.example.com/)\n")->getContent();

        $this->assertTrue($this->isExternal($html, 'https://request.example.com/'));
    }

    #[Test]
    public function aHostThatIsNotAValidNameIsNotUsed(): void
    {
        // CommonMark would take a value that starts with `/` as a regular
        // expression, and `/.*/` matches every host.
        // An IPv6 address is not used either: CommonMark encodes its brackets in
        // the links, so it would never match.
        foreach (['/.*/', 'evil.example.com/path', 'bad host', '', '[::1]:8080'] as $host) {
            $_SERVER['HTTP_HOST'] = $host;

            $html = (new MarkdownCreator())->create()
                ->convert("[a](https://anything.example.com/)\n")->getContent();

            $this->assertTrue($this->isExternal($html, 'https://anything.example.com/'), 'Host: ' . $host);
        }
    }

    #[Test]
    public function theHostOfTheRequestIsReadEveryTimeAConverterIsCreated(): void
    {
        $creator = new MarkdownCreator();

        $_SERVER['HTTP_HOST'] = 'first.example.com';
        $first = $creator->create()->convert("[a](https://first.example.com/)\n")->getContent();

        $_SERVER['HTTP_HOST'] = 'second.example.com';
        $second = $creator->create()->convert("[a](https://first.example.com/) [b](https://second.example.com/)\n")->getContent();

        $this->assertFalse($this->isExternal($first, 'https://first.example.com/'));
        $this->assertTrue($this->isExternal($second, 'https://first.example.com/'));
        $this->assertFalse($this->isExternal($second, 'https://second.example.com/'));
    }
}
