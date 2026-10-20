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

use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

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
     * The title is the one of the marker line, or the default one of the type.
     * The body is the content, whatever blocks it has.
     *
     * @param Node $node
     * @param ChildNodeRendererInterface $childRenderer
     * @return HtmlElement
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer)
    {
        if (!($node instanceof Admonition)) {
            throw new InvalidArgumentException([
                'Invalid node type: {type}',
                'type' => get_class($node),
            ]);
        }

        $type = $node->getType();
        $typeConfig = $this->config->getConfig($type);
        $title = $node->getTitle() !== '' ? $node->getTitle() : $typeConfig['title'];

        $titleElement = new HtmlElement(
            'div',
            ['class' => 'admonition-title lead mb-2 text-uppercase'],
            new HtmlElement('i', ['class' => $typeConfig['icon'], 'aria-hidden' => 'true']) . ' ' .
            new HtmlElement('strong', [], Xml::escape($title))
        );

        $contentElement = new HtmlElement(
            'div',
            ['class' => 'admonition-body'],
            $childRenderer->renderNodes($node->children())
        );

        return new HtmlElement(
            'div',
            ['class' => ['alert', $typeConfig['bootstrap_class'], 'admonition', 'admonition-' . $type]],
            $titleElement . "\n" . $contentElement
        );
    }
}
