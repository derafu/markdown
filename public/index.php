<?php

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\Markdown\Service\MarkdownService;

// Initialize the Markdown service.
$markdownService = new MarkdownService();

// Determine the base path of the application (useful for linking assets).
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Get the requested URL path, stripping the leading slash.
$request = ltrim($_SERVER['REQUEST_URI'], '/');

// Define the template path based on the request.
// - If no specific page is requested, it loads the README.md.
// - Otherwise, it looks for a Markdown file in the "pages" directory.
$template = ($request ? ('pages/' . $request . '.html') : '../README') . '.md';
$templateFilename = realpath(__DIR__ . '/' . $template);

// If the requested file does not exist, fallback to a 404 error page.
if (!$templateFilename) {
    $templateFilename = __DIR__ . '/error404.md';
}

// Render the Markdown content within the specified layout.
$data = [
    '__view_layout' => __DIR__ . '/layout.php',
    '__base_path' => $basePath,
];
echo $markdownService->render(realpath($templateFilename), $data);
