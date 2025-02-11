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
echo $markdownService->render(realpath($templateFilename), [
    '__view_layout' => __DIR__ . '/layout.php',
    '__base_path' => $basePath,
]);

/**
 * 🚀 Observations and possible improvements:
 *
 * 1️⃣ **Handle URLs without `.md` manually:**
 *    - Right now, if a user visits `/about`, it looks for `pages/about.html.md`.
 *    - Consider checking if `.md` is missing and appending it automatically.
 *
 * 2️⃣ **Fix issues with trailing slashes (`/`) in URLs:**
 *    - `/about/` currently results in searching for `pages/about/.html.md`, which is incorrect.
 *    - A simple `rtrim($request, '/')` before processing can fix this.
 *
 * 3️⃣ **Security Considerations:**
 *    - The script loads Markdown files dynamically based on user input.
 *    - Consider sanitizing `$request` to prevent unintended file access outside `pages/`.
 *
 * 4️⃣ **Improve Error Handling:**
 *    - If `error404.md` is missing, the script could fail.
 *    - You might want to return an explicit "404 Not Found" HTTP header using `header("HTTP/1.1 404 Not Found");`.
 */
