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
use InvalidArgumentException;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;

/**
 * Service for rendering HTML templates from Markdown.
 *
 * This service initializes League\CommonMark, configuring the most relevant
 * options and extensions to provide enhanced Markdown rendering.
 */
class MarkdownService
{
    /**
     * Instance of the Markdown converter.
     *
     * @var MarkdownConverter
     */
    private MarkdownConverter $markdown;

    /**
     * Constructor to initialize the service and its dependencies.
     *
     * @param MarkdownCreatorInterface $markdownCreator Factory for creating a
     * Markdown converter instance.
     */
    public function __construct(
        private MarkdownCreatorInterface $markdownCreator
    ) {
    }

    /**
     * Renders a Markdown template and returns the generated HTML as a string.
     *
     * @param string $filepath Path to the Markdown template to be rendered.
     * @param array $data Data to be passed to the template for variable substitution.
     * @return string The generated HTML content.
     */
    public function render(string $filepath, array $data = []): string
    {
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
     * Converts Markdown content into HTML.
     *
     * Also applies design-related adjustments to the rendered content.
     *
     * @param string $markdownContent The raw Markdown content.
     * @param array $data Reference to the data array, allowing metadata extraction.
     * @return string The processed HTML output.
     */
    public function renderFromString(string $markdownContent, array &$data = []): string
    {
        $result = $this->getMarkdown()->convert($markdownContent);
        $content = $result->getContent();

        $config = $this->getMarkdown()->getEnvironment()->getConfiguration();
        $content = '<div class="markdown-body">' . $content . '</div>';
        $content = str_replace(
            [
                htmlspecialchars($config->get('heading_permalink')['symbol']),
            ],
            [
                $config->get('heading_permalink')['symbol'],
            ],
            $content
        );

        // Extract Front Matter metadata if available.
        if ($result instanceof RenderedContentWithFrontMatter) {
            $frontMatter = $result->getFrontMatter();
            $data = array_merge($data, $frontMatter);
        }

        return $content;
    }

    /**
     * Lazily retrieves the Markdown converter instance.
     *
     * This ensures that the instance is only created when first accessed,
     * which is useful in lazy service loading scenarios.
     *
     * @return MarkdownConverter The Markdown converter instance.
     */
    public function getMarkdown(): MarkdownConverter
    {
        if (!isset($this->markdown)) {
            $this->markdown = $this->markdownCreator->create();
        }

        return $this->markdown;
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
                $content = preg_replace(
                    '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/',
                    $value,
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
     * @throws InvalidArgumentException If the provided layout path is not absolute.
     */
    private function resolveLayout(string $layout): string
    {
        if ($layout[0] === '/') {
            return $layout;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid layout path: %s. It must be an absolute path.',
            $layout
        ));
    }

    /**
     * Renders a PHP layout and injects the Markdown-generated content.
     *
     * @param string $layout The absolute path to the layout file.
     * @param array $data Data variables to be made available in the layout.
     * @return string The final rendered HTML.
     */
    private function renderLayout(string $layout, array $data): string
    {
        ob_start();
        extract($data);
        require $layout;

        return ob_get_clean();
    }
}
