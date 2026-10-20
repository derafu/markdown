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

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Admonition start parser.
 *
 * It starts with a line like `> [!TYPE]`, optionally followed by a title. It
 * must be registered before the one of the block quotes, which would take the
 * line.
 */
final class AdmonitionStartParser implements BlockStartParserInterface
{
    /**
     * Try to start a new admonition block.
     *
     * @param Cursor $cursor
     * @param MarkdownParserStateInterface $parserState
     * @return BlockStart|null
     */
    public function tryStart(
        Cursor $cursor,
        MarkdownParserStateInterface $parserState
    ): ?BlockStart {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '>') {
            return BlockStart::none();
        }

        if (!preg_match('/^\s*>\s*\[!(\w+)\][ \t]*(.*)$/', $cursor->getRemainder(), $matches)) {
            return BlockStart::none();
        }

        // The line of the marker has nothing else: the title was read.
        $cursor->advanceToEnd();

        return BlockStart::of(new AdmonitionContinueParser($matches[1], trim($matches[2])))
            ->at($cursor)
        ;
    }
}
