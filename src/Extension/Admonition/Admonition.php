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
     */
    public function __construct(string $type)
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
}
