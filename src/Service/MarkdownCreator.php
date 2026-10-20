<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Service;

use Derafu\Markdown\Contract\MarkdownCreatorInterface;
use Derafu\Markdown\Extension\Admonition\AdmonitionExtension;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Embed\Embed;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Embed\Bridge\OscaroteroEmbedAdapter;
use League\CommonMark\Extension\Embed\EmbedExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Creates and configures an instance of the Markdown converter.
 */
class MarkdownCreator implements MarkdownCreatorInterface
{
    /**
     * A host name (letters, digits, hyphens and dots), or an IPv4 address.
     *
     * An IPv6 address is not one: CommonMark encodes the brackets of the URLs
     * (`%5B`), so it would never match the host of a link.
     */
    private const HOST_PATTERN = '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*$/i';

    /**
     * Default options for the Markdown environment.
     *
     * @var array{extensions: list<class-string<ExtensionInterface>>, environment: array<string, mixed>}
     */
    private array $options = [
        'extensions' => [
            // From CommonMark.
            AttributesExtension::class,             // Custom attributes for elements.
            CommonMarkCoreExtension::class,         // Core CommonMark support.
            DefaultAttributesExtension::class,      // Default HTML classes and other attributes.
            DescriptionListExtension::class,        // Description lists.
            EmbedExtension::class,                  // Rich media embedding (videos, tweets, etc.).
            ExternalLinkExtension::class,           // External link handling.
            FootnoteExtension::class,               // Footnotes support.
            FrontMatterExtension::class,            // YAML Front Matter support.
            GithubFlavoredMarkdownExtension::class, // GitHub Flavored Markdown (GFM) support.
            HeadingPermalinkExtension::class,       // Permalinks for headings.
            MentionExtension::class,                // User and issue mention linking.
            SmartPunctExtension::class,             // Smart punctuation handling.
            TableOfContentsExtension::class,        // Table of contents generation.
            // From Derafu.
            AdmonitionExtension::class,             // Admonition support.
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
                'html_class' => 'heading-permalink text-decoration-none small text-muted', // CSS class for permalinks.
                'heading_class' => 'heading-anchor', // CSS class for the headings, to show the permalink on hover.
                'id_prefix' => '',                  // Prefix for heading IDs.
                'fragment_prefix' => '',            // Prefix for URL fragments.
                'insert' => 'after',                // Position of the permalink (after heading).
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
            'default_attributes' => [
                FencedCode::class => [
                    'class' => ['line-numbers', 'match-braces'],
                ],
            ],
        ],
    ];

    /**
     * Constructor to initialize the Markdown converter with options.
     *
     * @param array<string, mixed> $options Configuration options for Markdown.
     * @throws InvalidArgumentException If an option is not valid.
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

        // Create the environment with the resolved configuration. The host of
        // the current request is the internal host of the links, unless the
        // options say otherwise.
        $environment = new Environment(array_replace_recursive(
            $this->requestEnvironment(),
            $options['environment']
        ));

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
     * The `extensions` of the user are added to the ones that are already
     * there (an extension is never repeated), and the `environment` of the
     * user is merged recursively over the one that is already there.
     *
     * @param array<string, mixed> $options User-defined options for customizing the Markdown environment.
     * @return array{extensions: list<class-string<ExtensionInterface>>, environment: array<string, mixed>} The merged configuration options.
     * @throws InvalidArgumentException If an option is not valid.
     */
    private function resolveOptions(array $options): array
    {
        // Merge default options with user-provided options.
        $extensions = array_values(array_unique([
            ...$this->options['extensions'],
            ...$this->validExtensions($options['extensions'] ?? []),
        ]));
        $environment = array_replace_recursive(
            $this->options['environment'],
            $this->stringKeyed($options['environment'] ?? [], 'environment')
        );

        // Configure the 'embed' extension if settings are provided.
        if (isset($environment['embed']) && is_array($environment['embed'])) {
            $library = $this->stringKeyed($environment['embed']['library'] ?? [], 'environment.embed.library');
            if ($library !== []) {
                $embedLibrary = new Embed();
                $embedLibrary->setSettings($library);
                $environment['embed']['adapter'] =
                    new OscaroteroEmbedAdapter($embedLibrary)
                ;
            }

            // Remove the library settings after embedding configuration.
            unset($environment['embed']['library']);
        }

        // Return the final resolved configuration.
        return ['extensions' => $extensions, 'environment' => $environment];
    }

    /**
     * Configuration of the environment that comes from the current HTTP
     * request: its host is the internal host of the links (the ones to it are
     * not external links).
     *
     * It goes under the options, so an `internal_hosts` that was given always
     * wins, and an empty list says that there are no internal hosts.
     *
     * The host is only the name, without the port (CommonMark compares the
     * host of the link, which has none), and only if it is a valid name: the
     * header of the request is not trusted, and CommonMark takes a value that
     * starts with `/` as a regular expression.
     *
     * @return array<string, mixed>
     */
    private function requestEnvironment(): array
    {
        $host = $_SERVER['HTTP_HOST'] ?? null;
        if (!is_string($host)) {
            return [];
        }

        $name = parse_url('//' . $host, PHP_URL_HOST);
        if (!is_string($name) || preg_match(self::HOST_PATTERN, $name) !== 1) {
            return [];
        }

        return ['external_link' => ['internal_hosts' => $name]];
    }

    /**
     * Checks the extensions that the user gives.
     *
     * @return list<class-string<ExtensionInterface>>
     * @throws InvalidArgumentException If it is not a list of classes that
     * implement the interface of the extensions.
     */
    private function validExtensions(mixed $extensions): array
    {
        if (!is_array($extensions)) {
            throw new InvalidArgumentException([
                'The option {option} must be an array; {type} given.',
                'option' => 'extensions',
                'type' => get_debug_type($extensions),
            ]);
        }

        $valid = [];
        foreach ($extensions as $extension) {
            if (!is_string($extension) || !is_a($extension, ExtensionInterface::class, true)) {
                throw new InvalidArgumentException([
                    'The extension {extension} must be a class that implements {interface}.',
                    'extension' => is_string($extension) ? $extension : get_debug_type($extension),
                    'interface' => ExtensionInterface::class,
                ]);
            }
            $valid[] = $extension;
        }

        return $valid;
    }

    /**
     * Checks that an option is an array with names as keys.
     *
     * @return array<string, mixed>
     * @throws InvalidArgumentException If it is not an array, or if it has a
     * key that is not a name.
     */
    private function stringKeyed(mixed $value, string $option): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException([
                'The option {option} must be an array; {type} given.',
                'option' => $option,
                'type' => get_debug_type($value),
            ]);
        }

        $valid = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new InvalidArgumentException([
                    'The keys of the option {option} must be names; {key} given.',
                    'option' => $option,
                    'key' => $key,
                ]);
            }
            $valid[$key] = $item;
        }

        return $valid;
    }
}
