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

use Derafu\Markdown\Contract\MarkdownCreatorInterface;
use Derafu\Markdown\Contract\MarkdownServiceInterface;
use Derafu\Markdown\Extension\Admonition\AdmonitionConfig;
use Derafu\Markdown\Translation\MarkdownTranslationResourceProvider;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Loads the package's actual configuration through YAML consumer fixtures.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    /** @return ServiceLocator<object> */
    private function container(string $configuration = 'services.yaml'): ServiceLocator
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2));
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/fixtures/services'));
        $loader->load($configuration);
        self::assertArrayHasKey(
            MarkdownTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->compile();
        $services = $container->get('test.markdown_services');
        self::assertInstanceOf(ServiceLocator::class, $services);

        return $services;
    }

    public function testConfigurationConstructsEveryDeclaredPackageService(): void
    {
        $services = $this->container();
        $creator = $services->get(MarkdownCreatorInterface::class);
        self::assertInstanceOf(MarkdownCreatorInterface::class, $creator);
        self::assertSame("<p><strong>Creator</strong></p>\n", $creator->create()->convert('**Creator**')->getContent());
        $markdown = $services->get(MarkdownServiceInterface::class);
        self::assertInstanceOf(MarkdownServiceInterface::class, $markdown);
        self::assertSame('<div class="markdown-body"><p><strong>Service</strong></p>' . "\n</div>", $markdown->renderFromString('**Service**'));
        self::assertSame($markdown->getMarkdown(), $markdown->getMarkdown());
    }

    public function testConfiguredOptionsReachBothTheCreatorAndTheActualMarkdownService(): void
    {
        $services = $this->container('configured.yaml');
        $creator = $services->get(MarkdownCreatorInterface::class);
        self::assertInstanceOf(MarkdownCreatorInterface::class, $creator);
        $markdown = $services->get(MarkdownServiceInterface::class);
        self::assertInstanceOf(MarkdownServiceInterface::class, $markdown);
        $input = "# Configured heading\n\n<b>Raw HTML</b>";
        foreach ([$creator->create()->convert($input)->getContent(), $markdown->renderFromString($input)] as $html) {
            self::assertStringContainsString('class="configured-permalink"', $html);
            self::assertStringContainsString('>#</a>', $html);
            self::assertStringNotContainsString('<b>', $html);
            self::assertStringContainsString('Raw HTML', $html);
        }
    }

    public function testConfiguredPathsLoadARealTemplateBindDataAndExtractFrontMatter(): void
    {
        $markdown = $this->container('configured.yaml')->get(MarkdownServiceInterface::class);
        self::assertInstanceOf(MarkdownServiceInterface::class, $markdown);
        $data = ['name' => 'Reader'];
        $html = $markdown->render('article', $data);
        self::assertStringContainsString('Hello Reader', $html);
        self::assertStringContainsString('<strong>real Markdown</strong>', $html);
        self::assertStringContainsString('class="configured-permalink"', $html);
        self::assertSame('Service fixture', $data['title']);
        self::assertSame('Reader', $data['name']);
    }

    public function testConfigurationConstructsTheTranslationProvider(): void
    {
        $provider = $this->container()->get(MarkdownTranslationResourceProvider::class);
        self::assertInstanceOf(MarkdownTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        self::assertCount(1, $directories);
        self::assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateARealAdmonitionConfigurationException(): void
    {
        $services = $this->container();
        $translator = $services->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        $configuration = $services->get('admonition_config');
        self::assertInstanceOf(AdmonitionConfig::class, $configuration);
        try {
            $configuration->addType('custom', []);
            self::fail('An incomplete admonition configuration was accepted.');
        } catch (TranslatableInvalidArgumentException $exception) {
            self::assertSame('Falta la clave obligatoria: bootstrap_class', $exception->trans($translator));
        }
    }
}
