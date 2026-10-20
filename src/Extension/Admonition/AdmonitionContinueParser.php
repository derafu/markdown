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
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/**
 * Admonition continue parser.
 *
 * An admonition is a container, like a block quote: it continues while the
 * lines start with `>`, and what is after the marker is parsed as its content
 * (paragraphs, lists, code, quotes...).
 */
final class AdmonitionContinueParser extends AbstractBlockContinueParser
{
    /**
     * Admonition block.
     *
     * @var Admonition
     */
    private Admonition $block;

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $title
     */
    public function __construct(string $type, string $title = '')
    {
        $this->block = new Admonition($type, $title);
    }

    /**
     * Get admonition block.
     *
     * @return AbstractBlock
     */
    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    /**
     * Check if the block is a container.
     *
     * @return bool
     */
    public function isContainer(): bool
    {
        return true;
    }

    /**
     * Check if the block can contain a child block.
     *
     * @param AbstractBlock $childBlock
     * @return bool
     */
    public function canContain(AbstractBlock $childBlock): bool
    {
        return true;
    }

    /**
     * Try to continue the block: the line has to start with the quote marker.
     *
     * @param Cursor $cursor
     * @param BlockContinueParserInterface $activeBlockParser
     * @return BlockContinue|null
     */
    public function tryContinue(
        Cursor $cursor,
        BlockContinueParserInterface $activeBlockParser
    ): ?BlockContinue {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '>') {
            return BlockContinue::none();
        }

        // Advance beyond the quote marker and the space that follows it.
        $cursor->advanceToNextNonSpaceOrTab();
        $cursor->advanceBy(1);
        $cursor->advanceBySpaceOrTab();

        return BlockContinue::at($cursor);
    }
}
