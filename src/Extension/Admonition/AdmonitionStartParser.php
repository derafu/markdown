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
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $currentLine = $cursor->getLine();
        $trimmedLine = trim($currentLine);

        if (!str_starts_with($trimmedLine, '> [!')) {
            return BlockStart::none();
        }

        if (preg_match('/^>\s*\[!(\w+)\]\s*(.*)$/i', $currentLine, $matches)) {
            $type = strtolower($matches[1]);
            $content = trim($matches[2]);

            $cursor->advanceToEnd();

            $parser = new AdmonitionContinueParser($type, $content);
            return BlockStart::of($parser)
                ->at($cursor)
                ->replaceActiveBlockParser()
            ;
        }

        return BlockStart::none();
    }
}
