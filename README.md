[![Banner of Sylius Theme Companion plugin](docs/images/banner.jpg)](https://monsieurbiz.com/agence-web-experte-sylius)

<h1 align="center">Theme Companion for Sylius</h1>

[![Theme Companion  Plugin license](https://img.shields.io/github/license/monsieurbiz/SyliusThemeCompanionPlugin?public)](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/blob/master/LICENSE)
[![Tests](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/tests.yaml/badge.svg)](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/tests.yaml)
[![Security](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/security.yaml/badge.svg)](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/security.yaml)
[![Flex Recipe](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/recipe.yaml/badge.svg)](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/actions/workflows/recipe.yaml)

A powerful companion plugin for Sylius themes with asset management, build pipelines, and package management.

## Compatibility

| Sylius Version | PHP Version |
|----------------|-------------|
| 2.0            | 8.2 - 8.3   |
| 1.13+          | 8.2 - 8.3   |

ℹ️ For Sylius 1.12, see our [1.x branch](https://github.com/monsieurbiz/SyliusThemeCompanionPlugin/tree/1.x) and all 1.x releases.

## Installation

If you want to use our recipes, you can configure your composer.json by running:

```bash
composer config --no-plugins --json extra.symfony.endpoint '["https://api.github.com/repos/monsieurbiz/symfony-recipes/contents/index.json?ref=flex/master","flex://defaults"]'
```

```bash
composer require monsieurbiz/sylius-theme-companion-plugin
```

<details><summary>For the installation without flex, follow these additional steps</summary>
<p>

Change your `config/bundles.php` file to add this line for the plugin declaration:

```php
<?php

return [
    //..
    MonsieurBiz\SyliusThemeCompanionPlugin\MonsieurBizSyliusThemeCompanionPlugin::class => ['all' => true],
];
```

Then import the routes in `config/routes/monsieurbiz_sylius_theme_companion_plugin.yaml`:

```yaml
when@dev:
    monsieurbiz_theme_companion:
        resource: '@MonsieurBizSyliusThemeCompanionPlugin/config/routing/theme.yaml'
        prefix: /_theme
```

</p>
</details>

## Quick Start

### 1. Enable Theme Companion

Add `need_companion: true` to your theme's `composer.json`:

```json
{
  "name": "acme/my-theme",
  "type": "sylius-theme",
  "extra": {
    "sylius-theme": {
      "title": "My Awesome Theme",
      "need_companion": true
    }
  }
}
```

That's it! Your theme is now enhanced with Theme Companion features and will appear in the channel configuration, even if it's a packaged theme outside the `themes/` folder.

💡 **See it in action:** Check out the [naked theme example](examples/themes/local/naked/composer.json)

### 2. Add Assets (Optional)

Place your assets in the `assets/` folder in your theme:

```
themes/my-theme/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── images/
│       └── logo.svg
├── templates/
└── composer.json
```

Assets are automatically available via Symfony Asset Mapper:

```twig
<link rel="stylesheet" href="{{ asset('@AcmeMyTheme/css/style.css') }}">
<script src="{{ asset('@AcmeMyTheme/js/app.js') }}"></script>
<img src="{{ asset('@AcmeMyTheme/images/logo.svg') }}" alt="Logo">
```

📁 **Example:** See the complete structure in [naked theme](examples/themes/local/naked/)

### 3. Configure Build Pipeline (Optional)

For SCSS, Tailwind, or other compilation needs, add a build pipeline:

```json
{
  "extra": {
    "sylius-theme": {
      "title": "My Theme",
      "need_companion": true,
      "build_pipeline": {
        "css": {
          "input": "%theme_companion.acme_my_theme.assets_path%/styles/main.scss",
          "output": "%theme_companion.current_theme.assets_generated_path%/css/style.css",
          "runner": "sass"
        }
      }
    }
  }
}
```

Supported runners: `sass`, `tailwind`, `copy`

📦 **Examples:**
- SCSS compilation: [original theme](examples/themes/local/original/composer.json)
- Tailwind config template: [tailwind.config.js.twig](templates/tailwind.config.js.twig)

## Commands

### Debug Themes

```bash
# List all themes
php bin/console debug:theme

# Show detailed theme information
php bin/console debug:theme acme/my-theme
```

### Install Dependencies

```bash
# Install theme dependencies (JS packages, Composer packages, etc.)
php bin/console theme:install acme/my-theme
```

### Build Assets

```bash
# Build theme assets once
php bin/console theme:build acme/my-theme

# Watch for changes and rebuild automatically (development mode)
php bin/console theme:watch acme/my-theme
```

## Configuration Options

### Naming Convention

The name of your theme will be used in two main places:

- **Asset Mapper prefix** (e.g., `{{ asset('@MonsieurbizSyliusFooTheme/css/style.css') }}`)
- **Parameter name** (e.g., `%theme_companion.monsieurbiz_sylius_foo_theme.assets_path%`)

If the name of your theme is `monsieurbiz/sylius-foo-theme` in the `composer.json` file:

|                     | Rule                       | Without custom prefix        | With custom prefix `My-Foo Theme` |
|---------------------|----------------------------|------------------------------|-----------------------------------|
| Asset Mapper prefix | Camel case prefixed with @ | @MonsieurbizSyliusFooTheme   | @MyFooTheme                       |
| Parameter name      | Snake case without @       | monsieurbiz_sylius_foo_theme | my_foo_theme                      |

### Custom Prefix

Control the Asset Mapper prefix:

```json
{
  "extra": {
    "sylius-theme": {
      "title": "My Theme",
      "need_companion": true,
      "prefix": "@MyCustomPrefix"
    }
  }
}
```

📄 **Example:** [naked theme](examples/themes/local/naked/composer.json#L8)

### JavaScript Dependencies

Manage JS packages with Asset Mapper:

```json
{
  "extra": {
    "sylius-theme": {
      "dependencies": {
        "js": {
          "package_file": "%theme_companion.acme_my_theme.assets_path%/package.json",
          "package_manager": "asset_mapper"
        }
      }
    }
  }
}
```

Then create `assets/package.json` containing your dependencies:

```json
{
  "dependencies": {
    "alpinejs": "^3.0"
  }
}
```

📄 **Example:** [naked theme dependencies](examples/themes/local/naked/composer.json#L14-L19)

### Theme Variables

Pass variables to Twig templates:

```json
{
  "extra": {
    "sylius-theme": {
      "vars": {
        "twig": {
          "logo_text": "My Shop",
          "primary_color": "#ff6b6b"
        }
      }
    }
  }
}
```

Access in templates: `{{ theme.getVar('twig', 'logo_text') }}`

📄 **Example:** [naked theme variables](examples/themes/local/naked/composer.json#L9-L13)

### Parent Themes

Inherit configuration from another theme:

```json
{
  "extra": {
    "sylius-theme": {
      "title": "Child Theme",
      "need_companion": true,
      "parents": ["acme/base-theme"]
    }
  }
}
```

Child themes inherit build pipelines, dependencies, and variables from parent themes.

📄 **Example:** [quartz theme](examples/themes/local/quartz/composer.json#L8)

### Asset Mapper Configuration

Add additional Asset Mapper paths or exclude patterns:

```json
{
  "extra": {
    "sylius-theme": {
      "assets_managers": {
        "asset_mapper": {
          "paths": {
            "%kernel.project_dir%/vendor/some/package/assets": "@some-package"
          },
          "excluded_patterns": [
            "*/node_modules/*"
          ]
        }
      }
    }
  }
}
```

📄 **Example:** [original theme](examples/themes/local/original/composer.json#L9-L19)

### Build Pipeline Options

#### SCSS/Sass Compilation

```json
{
  "build_pipeline": {
    "css": {
      "input": "%theme_companion.acme_my_theme.assets_path%/styles/main.scss",
      "output": "%theme_companion.current_theme.assets_generated_path%/css/style.css",
      "runner": "sass",
      "config": {
        "load_path": [
          "%kernel.project_dir%/vendor/twbs/bootstrap/scss"
        ]
      }
    }
  }
}
```

#### Tailwind CSS

```json
{
  "build_pipeline": {
    "css": {
      "input": "%theme_companion.acme_my_theme.assets_path%/styles/main.css",
      "output": "%theme_companion.current_theme.assets_generated_path%/css/style.css",
      "runner": "tailwind",
      "config": {
        "minify": true,
        "config_file_template": "@MonsieurBizSyliusThemeCompanionPlugin/tailwind.config.js.twig"
      }
    }
  }
}
```

#### Copy Files

```json
{
  "build_pipeline": {
    "copy_fonts": {
      "input": "%kernel.project_dir%/vendor/some/package/fonts",
      "output": "%theme_companion.current_theme.assets_generated_path%/fonts",
      "runner": "copy"
    }
  }
}
```

📄 **Example:** [original theme build pipeline](examples/themes/local/original/composer.json#L21-L40)

## Examples

Full working examples are available in the [`examples/themes/`](examples/themes/) directory:

- **[naked](examples/themes/local/naked/)** - Simple theme with static CSS and Alpine.js
  - Shows basic asset structure
  - Asset Mapper integration
  - JavaScript dependencies

- **[original](examples/themes/local/original/)** - SCSS compilation with Asset Mapper
  - Build pipeline with Sass runner
  - JavaScript dependencies
  - Font file copying

- **[quartz](examples/themes/local/quartz/)** - Theme inheritance
  - Parent theme example
  - Shows configuration merging
  - SCSS variables override

- **[naked-override](examples/themes/local/naked-override/)** - Template overriding
  - Minimal override example
  - Child theme pattern

- **[packaged](examples/themes/packaged/)** - Theme as Composer package
  - Distribution example
  - Shows package-based themes

## Development Tools

### Theme Switcher (Dev Mode Only)

Quickly switch themes during development without modifying the database:

```
http://localhost/_theme/switch?theme=acme/my-theme
```

This is only available in development mode and requires the dev routes to be imported.

### Debug Toolbar

View current theme information in the Symfony profiler toolbar. The toolbar shows:
- Active theme name
- Theme prefix
- Parent themes
- Build pipeline status

## Contributing

We welcome contributions! Please see our [contribution guidelines](CONTRIBUTING.md) for details.

## License

This plugin is under the MIT license. See the [LICENSE](LICENSE) file for details.

## Credits

Developed by [Monsieur Biz](https://monsieurbiz.com/), expert Sylius agency.
