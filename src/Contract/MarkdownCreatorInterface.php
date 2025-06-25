<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Contract;

use League\CommonMark\MarkdownConverter;

/**
 * Interface for MarkdownCreator service.
 */
interface MarkdownCreatorInterface
{
    /**
     * Creates and returns a new instance of the Markdown converter.
     *
     * This method initializes the CommonMark environment, applies the
     * configured extensions, and returns a ready-to-use Markdown converter
     * instance.
     *
     * @param array $options Additional options for Markdown conversion.
     * @return MarkdownConverter The configured Markdown converter instance.
     */
    public function create(array $options = []): MarkdownConverter;
}
