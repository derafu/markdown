<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMarkdown;

use Derafu\Markdown\Service\MarkdownService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkdownService::class)]
class MarkdownServiceTest extends TestCase
{
    public function testSkipped(): void
    {
        $this->markTestSkipped('TODO: :)');
    }
}
