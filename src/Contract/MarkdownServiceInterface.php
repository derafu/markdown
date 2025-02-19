<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Contract;

use League\CommonMark\MarkdownConverter;

/**
 * Interface for MarkdownService.
 */
interface MarkdownServiceInterface
{
    /**
     * Renders a Markdown template and returns the generated HTML as a string.
     *
     * @param string $template File of the Markdown template to be rendered.
     * @param array $data Reference to the data array for variable substitution,
     * allowing metadata extraction.
     * @return string The generated HTML content.
     */
    public function render(string $template, array &$data = []): string;

    /**
     * Converts Markdown content into HTML.
     *
     * Also applies design-related adjustments to the rendered content.
     *
     * @param string $markdownContent The raw Markdown content.
     * @param array $data Reference to the data array for variable substitution,
     * allowing metadata extraction.
     * @return string The processed HTML output.
     */
    public function renderFromString(string $markdownContent, array &$data = []): string;

    /**
     * Lazily retrieves the Markdown converter instance.
     *
     * This ensures that the instance is only created when first accessed,
     * which is useful in lazy service loading scenarios.
     *
     * @return MarkdownConverter The Markdown converter instance.
     */
    public function getMarkdown(): MarkdownConverter;
}
