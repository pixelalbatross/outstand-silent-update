# Outstand Silent Update

> Update a post without changing its modified date in the Block Editor.

## Description

When you fix a typo, adjust formatting, or make minor edits to a published post, you may not want the "Last Modified" date to change. This plugin adds a **Silent Update** button to the Block Editor that saves your changes while preserving the original modified date.

This is useful when:

- Fixing typos or formatting on older content
- Updating internal links or media without signaling a content change
- Preventing RSS feeds from resurfacing old posts after minor edits
- Keeping "Last Updated" displays accurate to actual content changes

## How it works

1. Open a published post in the Block Editor.
2. Click the **Silent Update** button in the editor header toolbar.
3. A sidebar opens showing the current date and the last modified date that will be preserved.
4. Click **Save silently** to save your changes without updating the modified date.

The button only appears for published posts on supported post types.

## Installation

### Manual Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require outstand/silent-update
```

2. Run `composer install`.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Supported post types

By default, only the `post` post type is supported. You can extend this with the `outstand_silent_update_post_types` filter:

```php
add_filter( 'outstand_silent_update_post_types', function ( $post_types ) {
    $post_types[] = 'page';
    $post_types[] = 'my_custom_post_type';
    return $post_types;
} );
```

## Requirements

- WordPress 6.7+
- PHP 8.2+

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/pixelalbatross/outstand-silent-update/blob/main/CHANGELOG.md).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
