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

use InvalidArgumentException;

/**
 * Admonition config.
 */
final class AdmonitionConfig
{
    /**
     * Admonition types.
     *
     * @var array
     */
    private array $types = [
        'note' => [
            'aliases' => ['info'],
            'bootstrap_class' => 'alert-info',
            'icon' => 'fa-solid fa-circle-info fa-fw',
            'title' => 'Note',
        ],
        'tip' => [
            'aliases' => ['hint'],
            'bootstrap_class' => 'alert-success',
            'icon' => 'fa-solid fa-lightbulb fa-fw',
            'title' => 'Tip',
        ],
        'warning' => [
            'aliases' => ['caution'],
            'bootstrap_class' => 'alert-warning',
            'icon' => 'fa-solid fa-triangle-exclamation fa-fw',
            'title' => 'Warning',
        ],
        'danger' => [
            'aliases' => ['error'],
            'bootstrap_class' => 'alert-danger',
            'icon' => 'fa-solid fa-circle-exclamation fa-fw',
            'title' => 'Danger',
        ],
        'important' => [
            'aliases' => [],
            'bootstrap_class' => 'alert-warning',
            'icon' => 'fa-solid fa-circle-exclamation fa-fw',
            'title' => 'Important',
        ],
        'check' => [
            'aliases' => ['success'],
            'bootstrap_class' => 'alert-success',
            'icon' => 'fa-solid fa-circle-check fa-fw',
            'title' => 'Success',
        ],
        'question' => [
            'aliases' => ['help', 'faq'],
            'bootstrap_class' => 'alert-info',
            'icon' => 'fa-solid fa-circle-question fa-fw',
            'title' => 'Question',
        ],
        'example' => [
            'aliases' => [],
            'bootstrap_class' => 'alert-secondary',
            'icon' => 'fa-solid fa-list fa-fw',
            'title' => 'Example',
        ],
        'quote' => [
            'aliases' => ['cite'],
            'bootstrap_class' => 'alert-secondary',
            'icon' => 'fa-solid fa-quote-left fa-fw',
            'title' => 'Quote',
        ],
        'bug' => [
            'aliases' => ['issue'],
            'bootstrap_class' => 'alert-danger',
            'icon' => 'fa-solid fa-bug fa-fw',
            'title' => 'Bug',
        ],
    ];

    /**
     * Get admonition config.
     *
     * @param string $type
     * @return array
     */
    public function getConfig(string $type): array
    {
        $type = strtolower($type);

        // Search directly.
        if (isset($this->types[$type])) {
            return $this->types[$type];
        }

        // Search in aliases.
        foreach ($this->types as $mainType => $config) {
            if (in_array($type, $config['aliases'])) {
                return $config;
            }
        }

        // Fallback to note.
        return $this->types['note'];
    }

    /**
     * Add admonition type.
     *
     * @param string $type
     * @param array $config
     */
    public function addType(string $type, array $config): self
    {
        $requiredKeys = ['bootstrap_class', 'icon', 'title'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new InvalidArgumentException(sprintf(
                    'Missing required key: %s',
                    $key
                ));
            }
        }

        if (!isset($config['aliases'])) {
            $config['aliases'] = [];
        }

        $this->types[strtolower($type)] = $config;

        return $this;
    }
}
