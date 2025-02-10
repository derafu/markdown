<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Service;

use Derafu\Markdown\Contract\MarkdownCreatorInterface;
use Embed\Embed;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Embed\Bridge\OscaroteroEmbedAdapter;
use League\CommonMark\Extension\Embed\EmbedExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Creates and configures an instance of the Markdown converter.
 */
class MarkdownCreator implements MarkdownCreatorInterface
{
    /**
     * Default options for the Markdown environment.
     *
     * @var array
     */
    private $options = [
        'extensions' => [
            CommonMarkCoreExtension::class,         // Core CommonMark support.
            GithubFlavoredMarkdownExtension::class, // GitHub Flavored Markdown (GFM) support.
            TableOfContentsExtension::class,        // Table of contents generation.
            HeadingPermalinkExtension::class,       // Permalinks for headings.
            FootnoteExtension::class,               // Footnotes support.
            DescriptionListExtension::class,        // Description lists.
            AttributesExtension::class,             // Custom attributes for elements.
            SmartPunctExtension::class,             // Smart punctuation handling.
            ExternalLinkExtension::class,           // External link handling.
            FrontMatterExtension::class,            // YAML Front Matter support.
            MentionExtension::class,                // User and issue mention linking.
            EmbedExtension::class,                  // Rich media embedding (videos, tweets, etc.).
        ],
        'environment' => [
            'table_of_contents' => [
                'min_heading_level' => 2,           // Minimum heading level to include in the TOC.
                'max_heading_level' => 3,           // Maximum heading level to include in the TOC.
                'normalize' => 'relative',          // Normalize heading links.
                'position' => 'placeholder',        // Positioning of the TOC.
                'placeholder' => '[TOC]',           // Placeholder text for TOC placement.
            ],
            'heading_permalink' => [
                'html_class' => 'text-decoration-none small text-muted', // CSS class for permalinks.
                'id_prefix' => 'content',           // Prefix for heading IDs.
                'fragment_prefix' => 'content',     // Prefix for URL fragments.
                'insert' => 'before',               // Position of the permalink (before heading).
                'title' => 'Permalink',             // Tooltip text.
                'symbol' => '<i class="fa-solid fa-link"></i> ', // Symbol used for permalinks.
            ],
            'external_link' => [
                //'internal_hosts' => null,         // Only the domain/host (without HTTP scheme).
                'open_in_new_window' => true,       // Open external links in a new tab.
                'html_class' => 'external-link',    // CSS class for external links.
                'nofollow' => 'external',           // Add nofollow attribute.
                'noopener' => 'external',           // Add noopener attribute.
                'noreferrer' => 'external',         // Add noreferrer attribute.
            ],
            'mentions' => [
                // User mentions.
                '@' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => 'https://github.com/%s',
                ],
                // Issue mentions.
                '#' => [
                    'prefix' => '#',
                    'pattern' => '\d+',
                    'generator' => "https://github.com/derafu/markdown/issues/%d",
                ],
            ],
            'embed' => [
                //'adapter' => null, // new OscaroteroEmbedAdapter()
                'allowed_domains' => ['youtube.com'],   // Restrict embedding to specific domains.
                'fallback' => 'link',               // Fallback behavior if embed fails
                'library' => [                      // Embed library settings (will be removed after creation).
                    'oembed:query_parameters' => [
                        'maxwidth' => 400,          // Maximum embed width
                        'maxheight' => 300,         // Maximum embed height
                    ],
                ],
            ],
        ],
    ];

    /**
     * Constructor to initialize the Markdown converter with options.
     *
     * @param array $options Configuration options for Markdown.
     */
    public function __construct(array $options = [])
    {
        $this->options = $this->resolveOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $options = []): MarkdownConverter
    {
        // Resolve options for creating the Markdown environment.
        $options = $this->resolveOptions($options);

        // Create the environment with the resolved configuration.
        $environment = new Environment($options['environment']);

        // Register extensions for Markdown processing.
        foreach ($options['extensions'] as $extension) {
            $environment->addExtension(new $extension());
        }

        // Return the configured Markdown converter instance.
        return new MarkdownConverter($environment);
    }

    /**
     * Resolves configuration options for the Markdown environment.
     *
     * This method merges the default options with any user-provided settings,
     * ensuring that the environment is properly configured before use.
     *
     * @param array $options User-defined options for customizing the Markdown environment.
     * @return array The merged configuration options.
     */
    private function resolveOptions(array $options): array
    {
        // Configure 'external_link' settings.
        // Set the internal host dynamically based on the current HTTP request.
        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->options['environment']['external_link']['internal_hosts'] =
                $_SERVER['HTTP_HOST']
            ;
        }

        // Merge default options with user-provided options.
        $options = array_replace_recursive($this->options, $options);

        // Configure the 'embed' extension if settings are provided.
        if (!empty($options['environment']['embed']['library'])) {
            $embedLibrary = new Embed();
            $embedLibrary->setSettings($options['environment']['embed']['library']);
            $options['environment']['embed']['adapter'] =
                new OscaroteroEmbedAdapter($embedLibrary)
            ;
        }

        // Remove the library settings after embedding configuration.
        unset($options['environment']['embed']['library']);

        // Return the final resolved configuration.
        return $options;
    }
}
