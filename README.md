# Derafu: Markdown - PHP Markdown Rendering Library

![GitHub last commit](https://img.shields.io/github/last-commit/derafu/markdown/main)
![CI Workflow](https://github.com/derafu/markdown/actions/workflows/ci.yml/badge.svg?branch=main&event=push)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/derafu/markdown)
![GitHub Issues](https://img.shields.io/github/issues-raw/derafu/markdown)
![Total Downloads](https://poser.pugx.org/derafu/markdown/downloads)
![Monthly Downloads](https://poser.pugx.org/derafu/markdown/d/monthly)

**Derafu Markdown** is a PHP library that provides a powerful Markdown rendering engine with support for advanced extensions. It leverages `league/commonmark` and additional features to enhance Markdown processing for documentation, blogs, and dynamic content.

## Features

- 📝 **Full Markdown Support**: Standard CommonMark and GitHub Flavored Markdown (GFM).
- 📚 **Extended Capabilities**: TOC, footnotes, mentions, permalinks, embeds, and more.
- 🎨 **Custom Attributes**: Add CSS classes and IDs to elements.
- 🔗 **External Link Handling**: Open in new tabs, add `nofollow`, etc.
- 🛠 **Highly Configurable**: Fine-tune Markdown behavior with options.
- 📦 **Easy Integration**: Works standalone or within any PHP project.
- 🏷 **MIT Licensed**: Open-source and free to use.

> [!INFO] Admonition Extension
>
> This library includes an admonition extension to process common messages. For example: tip, info and warning.

## Installation

Install via Composer:

```bash
composer require derafu/markdown
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

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

**Derafu Markdown** simplifies integrating and configuring Markdown rendering in PHP applications, leveraging `league/commonmark` with enhanced defaults. 🚀
