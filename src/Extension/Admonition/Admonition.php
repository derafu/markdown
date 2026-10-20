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

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * Admonition block node.
 *
 * Its children are the blocks of the content (paragraphs, lists, code...).
 */
final class Admonition extends AbstractBlock
{
    /**
     * Admonition type.
     *
     * Supported types in AdmonitionConfig.php
     *
     * @var string
     */
    private string $type;

    /**
     * Constructor.
     *
     * @param string $type Type of the admonition.
     * @param string $title Title written in the line of the marker, or an
     * empty string to use the default title of the type.
     */
    public function __construct(string $type, private readonly string $title = '')
    {
        parent::__construct();
        $this->type = strtolower($type);
    }

    /**
     * Get admonition type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the title written in the line of the marker.
     *
     * @return string The title, or an empty string if there is none.
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
