---
__view_title: "Markdown Template Usage Guide"
---

# Markdown Template Usage Guide

This document is designed to help you maximize the capabilities of Markdown within your application. By default, various extensions are configured to expand Markdown functionalities, allowing you to create dynamic and well-styled content easily.

[TOC]

## 1. Introduction to Markdown

Markdown is a lightweight markup language that allows you to write plain text documents and easily convert them into HTML. Its simple syntax makes it a popular choice for documentation, blogs, and other web applications. Below are some basic Markdown examples:

### Headings

```markdown
# Level 1 Heading

## Level 2 Heading

### Level 3 Heading
```

### Emphasis

```markdown
**Bold Text**

*Italic Text*

~~Strikethrough Text~~
```

### Lists

```markdown
- Unordered list item
- Another item

1. Ordered list item
2. Another item
```

### Links and Images

```markdown
[Link to Google](https://www.google.com)

![Alternative Image Text](https://www.example.com/image.jpg)
```

## 2. Markdown Extensions

You can extend Markdown capabilities with various extensions that enhance your content. Below is a description of the included extensions and how to use them.

### 2.1. GitHub Flavored Markdown (GFM)

GFM is an extended version of Markdown used on GitHub, adding features such as tables, task lists, and syntax-highlighted code blocks. To use GFM, simply write your Markdown content, and the extension will handle the rest.

#### Examples:

**Task Lists**:

```markdown
- [x] Completed task
- [ ] Pending task
```

**Tables**:

```markdown
| Header 1 | Header 2 |
| -------- | -------- |
| Cell 1   | Cell 2   |
```

**Code Blocks**:

```markdown
```php
echo "This is PHP code";
```

### 2.2. Automatic Table of Contents (TOC)

The Table of Contents (TOC) extension allows you to automatically generate an index based on the document's headings. To include a TOC, simply place `[TOC]` where you want it to appear.

#### Example:

```markdown
[TOC]

## Section 1
Content of section 1.

## Section 2
Content of section 2.
```

### 2.3. Header Permalinks

Permalinks allow headers to have permanent links that users can copy and share easily. This extension automatically generates permalinks for all headers.

#### Example:

```markdown
### Important Header
```

This will automatically generate the permalink `#content-important-header`.

### 2.4. Footnotes

Footnotes allow you to add references or clarifications without disrupting the text flow. Use the syntax `[^1]` to mark a footnote and define its content at the bottom of the document.

#### Example:

```markdown
Here is a phrase with a footnote[^1].

[^1]: This is the footnote, appearing at the end of the document.
```

### 2.5. Definitions

Definition lists allow you to create glossaries or descriptive lists easily.

#### Example:

```markdown
Term 1
: Definition for term 1.

Term 2
: Definition for term 2.
```

### 2.6. Custom Attributes

This extension allows you to add custom HTML attributes to specific Markdown elements.

#### Example:

```markdown
### Header with Custom ID {.my-css-class}
```

### 2.7. External Links

You can define how external links are handled. You can configure them to open in a new window, add attributes like `nofollow`, and more.

#### Example:

```markdown
[External Link](https://www.google.com)
```

This will generate a link that opens in a new window with additional attributes like `rel="noopener noreferrer"`.

### 2.8. Mentions

Mentions allow you to link, by default, to GitHub profiles or issues directly from Markdown.

#### Example:

```markdown
Hello @user, please check issue #123.
```

This will generate links to `https://github.com/user` and `https://github.com/derafu/markdown/issues/123`.

### 2.9. Embeds

The embed extension allows you to insert content from sites like YouTube directly into your document.

#### Example:

```markdown
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

This will embed the YouTube video instead of just displaying the link.

## 3. Advanced Options

Check the `MarkdownCreator` service to see the extensive customization options available for handling Markdown in your application. You can modify aspects such as:

- ID prefixes for permalinks.
- Custom styles for footnotes and tables.
- Advanced settings for mentions and embeds.

### Assigning Options

You must assign options when creating the `MarkdownCreator` service by passing an array with the options you want to modify.

Example:

```php
$options = [
    'environment' => [
        'mentions' => [
            '@' => [
                'prefix' => 'https://github.com/',
                'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                'generator' => 'https://github.com/%s',
            ],
            '#' => [
                'prefix' => '#',
                'pattern' => '\d+',
                'generator' => 'https://github.com/derafu/markdown/issues/%d',
            ],
        ],
    ],
];
$creator = new MarkdownCreator($options);
```

## 4. Template Metadata Block

Markdown templates allow the inclusion of a metadata block placed at the beginning of the file. These metadata entries are in `YAML` format, and all defined keys will be passed to the rendered layout and available as extracted variables in it.

Example:

```markdown
---
__view_title: "Markdown Template Usage Guide"
---
```

With this metadata block, the `__view_title` index will be assigned to the layout data and can be used later as a variable within it.

## 5. Conclusion

The Markdown renderer used by this library, along with the preconfigured extensions, provides a powerful Markdown template rendering engine that allows you to create rich and dynamic documents effortlessly. With support for various extensions and advanced settings, you can fully customize the Markdown experience in your application.
