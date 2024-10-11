# Sift Decisions

This plugin will integrate with Sift Science's fraud detection suite and WooCommerce's decisions API.

## Folder Structure

This plugin has the following folder structure:

```
sift-decisions/
├── languages/
├── src/
│   ├ ...
│   └── inc/
└── sift-decisions.php
```

- languages: Contains the translation files for your plugin.
- src: A folder for organizing the plugin's main PHP classes or code components, such as integrations with other plugins or services. These classes should be organized into subfolders following the [PSR-4](https://www.php-fig.org/psr/psr-4/) convention. `Composer` will handle the autoloading for these classes.
- plugin-name.php: The main PHP file containing the plugin header and bootstraping functionality.

## Documentation

As you develop your plugin, update the README.md file with detailed information about your plugin's features, usage, installation, and any other pertinent information.

## Local Development

Use [`wp-env`](https://github.com/WordPress/gutenberg/tree/HEAD/packages/env#readme) to run a local development environment.

```bash
wp-env start
```
