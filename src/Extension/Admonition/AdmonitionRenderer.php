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

use InvalidArgumentException;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Admonition renderer.
 */
final class AdmonitionRenderer implements NodeRendererInterface
{
    /**
     * Admonition config.
     *
     * @var AdmonitionConfig
     */
    private AdmonitionConfig $config;

    /**
     * Constructor.
     *
     * @param AdmonitionConfig|null $config
     */
    public function __construct(?AdmonitionConfig $config = null)
    {
        $this->config = $config ?? new AdmonitionConfig();
    }

    /**
     * Render the admonition.
     *
     * @param Node $node
     * @param ChildNodeRendererInterface $childRenderer
     * @return HtmlElement
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (!($node instanceof Admonition)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid node type: %s',
                get_class($node)
            ));
        }

        $type = $node->getType();
        $typeConfig = $this->config->getConfig($type);

        $children = iterator_to_array($node->children());
        $firstChild = reset($children);

        if ($firstChild instanceof Paragraph) {
            $firstContent = $childRenderer->renderNodes($firstChild->children());
            $parts = explode("\n", $firstContent, 2);
            $firstLine = trim($parts[0]);

            // If the first line is empty or is [!TYPE], use the default title.
            if (empty($firstLine) || preg_match('/^\[![\w-]+\]$/', $firstLine)) {
                $title = $typeConfig['title'];
                array_shift($children); // Remove the first line that contains the type.
            } else {
                // If the first line is the type of admonition.
                if (preg_match('/^\[![\w-]+\]\s*(.*)$/', $firstLine, $matches)) {
                    $title = !empty($matches[1]) ? $matches[1] : $typeConfig['title'];
                    array_shift($children); // Remove the first line that contains the type and title.

                    // If there is content after the title on the same line, create a new paragraph.
                    if (isset($parts[1])) {
                        $newParagraph = new Paragraph();
                        $newParagraph->appendChild(new Text($parts[1]));
                        array_unshift($children, $newParagraph);
                    }
                } else {
                    $title = $firstLine;
                    array_shift($children); // Remove the first line that contains the title.
                }
            }

            // Process the content.
            $contentHtml = '';
            foreach ($children as $child) {
                $rendered = $childRenderer->renderNodes([$child]);
                if (!empty(trim(strip_tags($rendered)))) {
                    if (!empty($contentHtml)) {
                        $contentHtml .= "\n";
                    }
                    $contentHtml .= $rendered;
                }
            }

            $titleElement = new HtmlElement(
                'div',
                ['class' => 'admonition-title lead mb-2 text-uppercase'],
                new HtmlElement('i', ['class' => $typeConfig['icon'], 'aria-hidden' => 'true']) . ' ' .
                new HtmlElement('strong', [], $title)
            );

            $contentElement = new HtmlElement(
                'div',
                ['class' => 'admonition-body'],
                $contentHtml
            );

            return new HtmlElement(
                'div',
                ['class' => ['alert', $typeConfig['bootstrap_class'], 'admonition', 'admonition-' . $type]],
                $titleElement . "\n" . $contentElement
            );
        }

        // Fallback if there is no expected structure.
        return new HtmlElement(
            'div',
            ['class' => ['alert', $typeConfig['bootstrap_class'], 'admonition', 'admonition-' . $type]],
            $childRenderer->renderNodes($node->children())
        );
    }
}
