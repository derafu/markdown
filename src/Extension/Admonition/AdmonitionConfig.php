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

/**
 * Admonition config.
 */
final class AdmonitionConfig
{
    /**
     * Admonition types.
     *
     * @var array<string, array{aliases: list<string>, bootstrap_class: string, icon: string, title: string}>
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
     * @return array{aliases: list<string>, bootstrap_class: string, icon: string, title: string}
     */
    public function getConfig(string $type): array
    {
        $type = strtolower($type);

        // Search directly.
        if (isset($this->types[$type])) {
            return $this->types[$type];
        }

        // Search in aliases.
        foreach ($this->types as $config) {
            if (in_array($type, $config['aliases'], true)) {
                return $config;
            }
        }

        // Fallback to note.
        return $this->types['note'];
    }

    /**
     * Add admonition type.
     *
     * It replaces the type if it already exists. The aliases are not case
     * sensitive.
     *
     * @param string $type
     * @param array<string, mixed> $config The `bootstrap_class`, the `icon` and
     * the `title` (strings), and the `aliases` (a list of strings, optional).
     * @throws InvalidArgumentException If a key is missing or is not valid.
     */
    public function addType(string $type, array $config): self
    {
        $bootstrapClass = $this->requiredString($config, 'bootstrap_class');
        $icon = $this->requiredString($config, 'icon');
        $title = $this->requiredString($config, 'title');

        $this->types[strtolower($type)] = [
            'aliases' => $this->aliases($config['aliases'] ?? []),
            'bootstrap_class' => $bootstrapClass,
            'icon' => $icon,
            'title' => $title,
        ];

        return $this;
    }

    /**
     * Gets a key of the config of a type, which is required and is a string.
     *
     * @param array<string, mixed> $config
     * @throws InvalidArgumentException If it is missing or is not a string.
     */
    private function requiredString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;
        if ($value === null) {
            throw new InvalidArgumentException([
                'Missing required key: {key}',
                'key' => $key,
            ]);
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException([
                'The key {key} must be a string; {type} given.',
                'key' => $key,
                'type' => get_debug_type($value),
            ]);
        }

        return $value;
    }

    /**
     * Normalizes the aliases of a type: a list of strings, in lowercase.
     *
     * @return list<string>
     * @throws InvalidArgumentException If it is not a list of strings.
     */
    private function aliases(mixed $aliases): array
    {
        if (!is_array($aliases) || !array_is_list($aliases)) {
            throw new InvalidArgumentException('The key aliases must be a list of strings.');
        }

        $normalized = [];
        foreach ($aliases as $alias) {
            if (!is_string($alias)) {
                throw new InvalidArgumentException('The key aliases must be a list of strings.');
            }
            $normalized[] = strtolower($alias);
        }

        return $normalized;
    }
}
