<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
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
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdmonitionConfig::class)]
#[UsesClass(Admonition::class)]
#[UsesClass(AdmonitionContinueParser::class)]
#[UsesClass(AdmonitionExtension::class)]
#[UsesClass(AdmonitionRenderer::class)]
#[UsesClass(AdmonitionStartParser::class)]
final class AdmonitionConfigTest extends TestCase
{
    /**
     * @return array{aliases?: list<string>, bootstrap_class: string, icon: string, title: string}
     */
    private static function deploy(): array
    {
        return [
            'bootstrap_class' => 'alert-primary',
            'icon' => 'fa-solid fa-rocket fa-fw',
            'title' => 'Deployment',
            'aliases' => ['Release'],
        ];
    }

    private function render(AdmonitionConfig $config, string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AdmonitionExtension($config));

        return (new MarkdownConverter($environment))->convert($markdown)->getContent();
    }

    #[Test]
    public function aTypeThatIsAddedIsRenderedWithItsConfig(): void
    {
        $config = (new AdmonitionConfig())->addType('deploy', self::deploy());

        $html = $this->render($config, "> [!DEPLOY]\n>\n> Shipped.\n");

        $this->assertStringContainsString('class="alert alert-primary admonition admonition-deploy"', $html);
        $this->assertStringContainsString('<i class="fa-solid fa-rocket fa-fw" aria-hidden="true"></i>', $html);
        $this->assertStringContainsString('<strong>Deployment</strong>', $html);
    }

    #[Test]
    public function theAliasesOfATypeAreNotCaseSensitive(): void
    {
        $config = (new AdmonitionConfig())->addType('Deploy', self::deploy());

        // `Release` was given with an uppercase letter, and the type is asked
        // in lowercase (or the other way around).
        foreach (['release', 'RELEASE', 'deploy', 'DEPLOY'] as $type) {
            $this->assertSame('Deployment', $config->getConfig($type)['title'], $type);
        }
        $this->assertSame(['release'], $config->getConfig('release')['aliases']);
        $this->assertStringContainsString(
            '<strong>Deployment</strong>',
            $this->render($config, "> [!RELEASE]\n>\n> Shipped.\n")
        );
    }

    #[Test]
    public function aTypeThatIsNotKnownUsesTheConfigOfNote(): void
    {
        $config = new AdmonitionConfig();

        $this->assertSame($config->getConfig('note'), $config->getConfig('nothing-like-it'));
    }

    #[Test]
    public function theAliasesOfTheDefaultTypesStillWork(): void
    {
        $config = new AdmonitionConfig();

        $this->assertSame($config->getConfig('warning'), $config->getConfig('caution'));
        $this->assertSame($config->getConfig('question'), $config->getConfig('FAQ'));
    }

    #[Test]
    public function aTypeThatAlreadyExistsIsReplaced(): void
    {
        $config = (new AdmonitionConfig())->addType('note', [
            'bootstrap_class' => 'alert-dark',
            'icon' => 'fa-solid fa-pen',
            'title' => 'Remark',
        ]);

        $this->assertSame('Remark', $config->getConfig('note')['title']);
        $this->assertSame([], $config->getConfig('note')['aliases']);
    }

    #[Test]
    public function addingATypeKeepsTheOthers(): void
    {
        $config = (new AdmonitionConfig())->addType('deploy', self::deploy());

        $this->assertSame('Warning', $config->getConfig('warning')['title']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidConfigs(): array
    {
        $valid = self::deploy();
        $without = static fn (string $key): array => array_diff_key($valid, [$key => true]);

        return [
            'without the class' => [$without('bootstrap_class'), 'Missing required key: bootstrap_class'],
            'without the icon' => [$without('icon'), 'Missing required key: icon'],
            'without the title' => [$without('title'), 'Missing required key: title'],
            'with a title that is null' => [['title' => null] + $valid, 'Missing required key: title'],
            'with a title that is not a string' => [['title' => 5] + $valid, 'The key title must be a string; int given.'],
            'with an icon that is not a string' => [['icon' => ['fa']] + $valid, 'The key icon must be a string; array given.'],
            'with a class that is not a string' => [['bootstrap_class' => true] + $valid, 'The key bootstrap_class must be a string; bool given.'],
            'with aliases that are not an array' => [['aliases' => 'faq'] + $valid, 'The key aliases must be a list of strings.'],
            'with aliases that are not a list' => [['aliases' => ['a' => 'faq']] + $valid, 'The key aliases must be a list of strings.'],
            'with an alias that is not a string' => [['aliases' => ['faq', 1]] + $valid, 'The key aliases must be a list of strings.'],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[Test]
    #[DataProvider('invalidConfigs')]
    public function aConfigThatIsNotValidIsRejectedAndTheTypeIsNotAdded(array $config, string $message): void
    {
        $admonitions = new AdmonitionConfig();

        try {
            $admonitions->addType('deploy', $config);
            $this->fail('A config that is not valid must be rejected.');
        } catch (TranslatableInvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
            $this->assertSame('Note', $admonitions->getConfig('deploy')['title']);
        }
    }
}
