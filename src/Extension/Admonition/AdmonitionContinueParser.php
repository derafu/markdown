<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Markdown\Extension\Admonition;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/**
 * Admonition continue parser.
 */
final class AdmonitionContinueParser implements BlockContinueParserInterface
{
    /**
     * Admonition block.
     *
     * @var Admonition
     */
    private Admonition $block;

    /**
     * Current paragraph.
     *
     * @var Paragraph
     */
    private Paragraph $currentParagraph;

    /**
     * Is first line.
     *
     * @var bool
     */
    private bool $isFirstLine = true;

    /**
     * Has title.
     *
     * @var bool
     */
    private bool $hasTitle = false;

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $firstLine
     */
    public function __construct(string $type, string $firstLine = '')
    {
        $this->block = new Admonition($type);
        $this->currentParagraph = new Paragraph();
        $this->block->appendChild($this->currentParagraph);
        $this->hasTitle = !empty($firstLine);

        if ($this->hasTitle) {
            $this->currentParagraph->appendChild(new Text($firstLine));
            $this->isFirstLine = false;
        }
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
     * Check if the block can have lazy continuation lines.
     *
     * @return bool
     */
    public function canHaveLazyContinuationLines(): bool
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
     * Try to continue the block.
     *
     * @param Cursor $cursor
     * @param BlockContinueParserInterface $activeBlockParser
     * @return BlockContinue|null
     */
    public function tryContinue(
        Cursor $cursor,
        BlockContinueParserInterface $activeBlockParser
    ): ?BlockContinue {
        $currentLine = $cursor->getLine();

        if (!str_starts_with(trim($currentLine), '>')) {
            return BlockContinue::none();
        }

        // If it is a line that begins with '> >', it is a quote inside the
        // admonition.
        if (preg_match('/^>\s*>\s*/', $currentLine)) {
            $cursor->advanceBy(strspn($currentLine, ' >'));
            return BlockContinue::at($cursor);
        }

        // Detect if it is a line that only contains '>' (empty line in
        // admonition).
        if (trim($currentLine) === '>') {
            if (!$this->hasTitle && $this->isFirstLine) {
                $this->isFirstLine = false;
            } else {
                $this->currentParagraph = new Paragraph();
                $this->block->appendChild($this->currentParagraph);
            }
            return BlockContinue::at($cursor);
        }

        // Advance beyond the quote marker and spaces.
        $cursor->advanceBy(strspn($currentLine, ' >'));

        return BlockContinue::at($cursor);
    }

    /**
     * Add a line to the block.
     *
     * @param string $line
     */
    public function addLine(string $line): void
    {
        // Remove the quote marker and initial spaces.
        $line = preg_replace('/^>\s*/', '', $line);

        // If it is the first line and there is no title, we ignore it because
        // it is the type [!TIP].
        if ($this->isFirstLine && !$this->hasTitle) {
            $this->isFirstLine = false;
            return;
        }

        // If the line is empty, create a new paragraph.
        if (trim($line) === '') {
            $this->currentParagraph = new Paragraph();
            $this->block->appendChild($this->currentParagraph);
            return;
        }

        // If it is the first line after the title, create a new paragraph.
        if ($this->isFirstLine) {
            $this->isFirstLine = false;
            $this->currentParagraph = new Paragraph();
            $this->block->appendChild($this->currentParagraph);
        }

        // Add the line to the current paragraph.
        if ($this->currentParagraph->firstChild() !== null) {
            $this->currentParagraph->appendChild(new Text("\n"));
        }
        $this->currentParagraph->appendChild(new Text($line));
    }

    /**
     * Close the block.
     */
    public function closeBlock(): void
    {
        // Ensure to add the last content if it exists.
        if ($this->currentParagraph->firstChild() !== null) {
            $this->currentParagraph->appendChild(new Text("\n"));
        }
    }
}
