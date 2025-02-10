# Derafu: Markdown - PHP Markdown Rendering Library

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

**Derafu Markdown** is a PHP library that provides a powerful Markdown rendering engine with support for advanced extensions. It leverages `league/commonmark` and additional features to enhance Markdown processing for documentation, blogs, and dynamic content.

## Features

- 📝 **Full Markdown Support**: Standard CommonMark and GitHub Flavored Markdown (GFM).
- 📚 **Extended Capabilities**: TOC, footnotes, mentions, permalinks, embeds, and more.
- 🎨 **Custom Attributes**: Add CSS classes and IDs to elements.
- 🔗 **External Link Handling**: Open in new tabs, add `nofollow`, etc.
- 🛠 **Highly Configurable**: Fine-tune Markdown behavior with options.
- 📦 **Easy Integration**: Works standalone or within any PHP project.
- 🏷 **MIT Licensed**: Open-source and free to use.

## Installation

Install via Composer:

```bash
composer require derafu/markdown
```

## Example webpage

To view the example webpage, run:

```bash
php -S localhost:9000 -t public
```

## Usage

### Basic Rendering

```php
use Derafu\Markdown\Service\MarkdownCreator;
use Derafu\Markdown\Service\MarkdownService;

$markdownService = new MarkdownService(new MarkdownCreator());

echo $markdownService->render('example.md');
```

### Rendering with Layout

```php
$markdownService->render('example.md', [
    '__view_layout' => 'layout.php',
    '__view_title' => 'My Markdown Page'
]);
```

## Available Extensions

### ✅ **GitHub Flavored Markdown (GFM)**
- Task lists:

```markdown
- [x] Completed
- [ ] Pending
```

- Tables:

```markdown
| Name  | Age |
|-------|-----|
| John  | 25  |
| Alice | 30  |
```

### 📌 **Table of Contents (TOC)**

```markdown
[TOC]

## Section 1
## Section 2
```

### 🔗 **Header Permalinks**

```markdown
### Important Header
```

Generates an anchor link like `#important-header`.

### 📝 **Footnotes**

```markdown
Here is a reference[^1].

[^1]: Footnote text.
```

### 🏷 **Custom Attributes**

```markdown
### Title {.custom-class}
```

### 🔗 **External Links Handling**

```markdown
[Google](https://www.google.com)
```

Adds attributes like `rel="noopener noreferrer"`.

### 📌 **Mentions & Issues**

```markdown
Hello @user, check issue #123.
```

Links to GitHub profiles and issues.

### 🎥 **Embeds**

```markdown
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

Automatically embeds videos.

## Advanced Configuration

You can customize the behavior of Markdown processing by passing an options array to `MarkdownCreator`:

```php
$options = [
    'environment' => [
        'mentions' => [
            '@' => ['generator' => 'https://github.com/%s'],
            '#' => ['generator' => 'https://github.com/derafu/markdown/issues/%d']
        ]
    ]
];

$creator = new MarkdownCreator($options);
```

## Template Metadata Support

Markdown templates can include metadata in YAML format:

```markdown
---
__view_title: "Markdown Template Guide"
---
```

## License

This library is licensed under the MIT License. See the `LICENSE` file for details.

## Contributions

Contributions are welcome! Open an issue or submit a pull request to improve this project.

---

**Derafu Markdown** simplifies integrating and configuring Markdown rendering in PHP applications, leveraging `league/commonmark` with enhanced defaults. 🚀
