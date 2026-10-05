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
use Derafu\Markdown\Contract\MarkdownServiceInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;

/**
 * Service for rendering HTML templates from Markdown.
 *
 * This service initializes League\CommonMark, configuring the most relevant
 * options and extensions to provide enhanced Markdown rendering.
 */
class MarkdownService implements MarkdownServiceInterface
{
    /**
     * Instance of the Markdown converter.
     *
     * @var MarkdownConverter
     */
    private MarkdownConverter $markdown;

    /**
     * Factory for creating a Markdown converter instance.
     *
     * @var MarkdownCreatorInterface
     */
    private MarkdownCreatorInterface $markdownCreator;

    /**
     * Paths for markdown templates.
     *
     * @var array
     */
    private array $paths;

    /**
     * Constructor to initialize the service and its dependencies.
     *
     * @param MarkdownCreatorInterface|array|null $markdownCreator Factory for
     * creating a Markdown converter instance.
     * @param array $paths Paths to markdown templates.
     */
    public function __construct(
        MarkdownCreatorInterface|array|null $markdownCreator = null,
        array $paths = []
    ) {
        if (is_array($markdownCreator)) {
            $markdownCreator = new MarkdownCreator($markdownCreator);
        }

        $this->markdownCreator = $markdownCreator ?? new MarkdownCreator();

        $this->paths = $paths;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $template, array &$data = []): string
    {
        $filepath = $this->resolveTemplate($template);
        $markdownContent = $this->loadTemplate($filepath, $data);
        $content = $this->renderFromString($markdownContent, $data);

        // Return the content directly if no layout is specified.
        if (empty($data['__view_layout'])) {
            return $content;
        }

        // Render the requested layout with the processed content included.
        $layout = $this->resolveLayout($data['__view_layout']);
        $data['__content'] = $content;

        return $this->renderLayout($layout, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function renderFromString(
        string $markdownContent,
        array &$data = []
    ): string {
        $result = $this->getMarkdown()->convert($markdownContent);
        $content = $result->getContent();

        $content = '<div class="markdown-body">' . $content . '</div>';

        // The symbol of the heading permalink is HTML (an icon), but
        // CommonMark escapes it when it renders it. Restore it, if the
        // converter has the permalinks.
        $config = $this->getMarkdown()->getEnvironment()->getConfiguration();
        if ($config->exists('heading_permalink/symbol')) {
            $symbol = $config->get('heading_permalink/symbol');
            $content = str_replace(htmlspecialchars($symbol), $symbol, $content);
        }

        // Extract Front Matter metadata if available.
        if ($result instanceof RenderedContentWithFrontMatter) {
            $frontMatter = $result->getFrontMatter();

            // The layout is a file that is included, so for now it can only be
            // defined by who renders, not by the content that is rendered.
            if (array_key_exists('__view_layout', $frontMatter)) {
                throw new InvalidArgumentException(
                    'Defining the layout with "__view_layout" in the front matter is not supported yet. Pass it in the data given to render() instead.'
                );
            }

            $data = array_merge($data, $frontMatter);
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function getMarkdown(): MarkdownConverter
    {
        if (!isset($this->markdown)) {
            $this->markdown = $this->markdownCreator->create();
        }

        return $this->markdown;
    }

    /**
     * Resolves template path.
     *
     * @param string $template Template name or path.
     * @return string Full template path.
     * @throws InvalidArgumentException If template cannot be found.
     */
    private function resolveTemplate(string $template): string
    {
        // If it is a path to an existing file (absolute, or relative to the
        // working directory), return it as is.
        if (is_file($template)) {
            return $template;
        }

        // Add extension if not present.
        if (
            !str_ends_with($template, '.md')
            && !str_ends_with($template, '.markdown')
        ) {
            $template .= '.md';

            // The same path, with the extension.
            if (is_file($template)) {
                return $template;
            }
        }

        // Search in configured paths.
        foreach ($this->paths as $path) {
            $file = $path . '/' . $template;
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new InvalidArgumentException([
            'Template {template} does not exists.',
            'template' => $template,
        ]);
    }

    /**
     * Loads a Markdown template file and processes variable placeholders.
     *
     * @param string $filepath The path to the Markdown file.
     * @param array $data The data to be injected into the template.
     * @return string The processed Markdown content.
     */
    private function loadTemplate(string $filepath, array $data): string
    {
        $content = file_get_contents($filepath);

        return $this->bindTemplate($content, $data);
    }

    /**
     * Replaces placeholders in a Markdown template with provided data.
     *
     * @param string $content The raw Markdown content.
     * @param array $data Key-value pairs for placeholder replacements.
     * @return string The processed content with replaced variables.
     */
    private function bindTemplate(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            if (
                is_scalar($value)
                || (is_object($value) && method_exists($value, '__toString'))
            ) {
                // A callback, so the value is inserted as it is: a replacement
                // string would interpret `$1` or `\1` in it.
                $content = preg_replace_callback(
                    '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                    fn () => (string) $value,
                    $content
                );
            }
        }

        return $content;
    }

    /**
     * Resolves the path to a layout file.
     *
     * @param string $layout The layout file path.
     * @return string The resolved absolute path.
     * @throws InvalidArgumentException If the provided layout path is not
     * absolute.
     */
    private function resolveLayout(string $layout): string
    {
        $realpath = realpath($layout);
        if ($realpath) {
            return $layout;
        }

        throw new InvalidArgumentException([
            'Invalid layout path: {layout}. It must be an absolute path.',
            'layout' => $layout,
        ]);
    }

    /**
     * Renders a PHP layout and injects the Markdown-generated content.
     *
     * @param string $__layout The absolute path to the layout file.
     * @param array $__data Data variables to be made available in the layout.
     * @return string The final rendered HTML.
     */
    private function renderLayout(string $__layout, array $__data): string
    {
        // EXTR_SKIP: a key of the data never replaces the variables of this
        // method, so it can not change the layout that is included.
        extract($__data, EXTR_SKIP);

        ob_start();
        require $__layout;

        return ob_get_clean();
    }
}
