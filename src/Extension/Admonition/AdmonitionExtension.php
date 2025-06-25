<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Extension\Admonition;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Markdown extension for admonition.
 */
final class AdmonitionExtension implements ExtensionInterface
{
    /**
     * Admonition config.
     *
     * @var AdmonitionConfig
     */
    private AdmonitionConfig $config;

    /**
     * Constructor.
     *
     * @param AdmonitionConfig|null $config
     */
    public function __construct(?AdmonitionConfig $config = null)
    {
        $this->config = $config ?? new AdmonitionConfig();
    }

    /**
     * Register the extension in the environment.
     *
     * @param EnvironmentBuilderInterface $environment
     */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new AdmonitionStartParser());
        $environment->addRenderer(
            Admonition::class,
            new AdmonitionRenderer($this->config)
        );
    }
}
