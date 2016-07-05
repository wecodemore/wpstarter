<!--
currentMenu: completeexample
title:  Complete Usage Example
-->
# Complete Usage Example

Sometimes out-of-the-box settings for WP Starter may be fine, sometimes not. In this page there's an usage example that will make use of
all WP Starter configuration. Explanation is provided below in this page.

# A *fully-equipped* `composer.json`

```json
{
    "name": "gmazzap/wpstarter-complete-example",
    "description": "Example project for WordPress + Composer + WP Starter",
    "type": "project",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "composer",
            "url": "https://wpackagist.org"
        },
        {
            "type": "vcs",
            "url": "https://gist.github.com/Giuseppe-Mazzapica/e8c8e4dfc8e65f1903ac.git"
        },
        {
            "type": "vcs",
            "url": "https://gist.github.com/Giuseppe-Mazzapica/9939793dfb2a2361cd0f.git"
        }
    ],
    "require": {
        "wecodemore/wpstarter": "~2.0",
        "johnpbloch/wordpress": ">=4.3",
        "gmazzap/wpstarter-example-files": "*"
    },
    "require-dev": {
        "wpackagist-plugin/query-monitor": "2.7.*",
        "wpackagist-plugin/debug-bar": "*",
        "rarst/laps": "~1.2",
        "wpackagist-plugin/plugin-profiler": "~1.0",
        "gmazzap/plugin-profiler-mu": "*"
    },
    "config": {
        "vendor-dir": "public/content/vendor",
        "optimize-autoloader": true
    },
    "scripts": {
        "post-install-cmd": "WCM\\WPStarter\\Setup::run",
        "post-update-cmd": "WCM\\WPStarter\\Setup::run",
        "wpstarter": "WCM\\WPStarter\\Setup::run"
    },
    "extra": {
        "wordpress-install-dir": "public/wp",
        "wordpress-content-dir": "public/content",
        "wpstarter": {
            "dropins": {
                "it_IT.php": "public/content/vendor/gmazzap/wpstarter-example-files/it_IT.php",
                "object-cache.php": "public/content/vendor/gmazzap/wpstarter-example-files/object-cache.php"
            },
            "prevent-overwrite": [
                ".gitignore",
                "public/wp-config.php",
                "public/index.php"
            ],
            "env-example": "public/content/vendor/gmazzap/wpstarter-example-files/.env.example",
            "gitignore": {
                "wp": true,
                "wp-content": true,
                "vendor": true,
                "common": true,
                "custom": [
                    "*.log",
                    ".htaccess",
                    "sitemap.xml",
                    "sitemap.xml.gz"
                ]
            }
        },
        "installer-paths": {
            "public/content/plugins/{$name}": [
                "type:wordpress-plugin"
            ],
            "public/content/mu-plugins/{$name}": [
                "type:wordpress-muplugin"
            ],
            "public/content/themes/{$name}": [
                "type:wordpress-theme"
            ]
        }
    }
}
```

## Example Explained

The example above may be a little scary, but reality is that great majority of WP Starter installations don't need a so much detailed configuration. E.g. the `composer.json` provided in the *"Quick Start"* docs section is a lot less complex and still perfectly functional.

Only reason to publish a so hardly customized `composer.json` is to show how WP Starter configuration allows a full control on any aspect of its work, but only if required.

All the configurations used in the example above are explained below, section by section.

### `"name"`, `"description"` and `"type"`  sections

This are simple Composer settings. See [official docs](https://getcomposer.org/doc/04-schema.md) to know more.

### `"minimum-stability"` section

By default Composer filters packages by stability, only installing one that are tagged as stable.

By using this setting ([see Compose docs](https://getcomposer.org/doc/04-schema.md#minimum-stability)) is possible to tell Composer to also install packages with different stability.

This is necessary in the example because some packages used are not stable and are added to project pulling them from Git repositories.


### `"repositories"` section

This is [a Composer setting](https://getcomposer.org/doc/04-schema.md#repositories) that allows to set custom repositories where to search for packages.

I've added 3 entries.

The first is [wpackagist](https://wpackagist.org). It is a mirror of the official WordPress plugin and theme directories as a Composer repository. In short, it allows to install all the plugins and themes available in official WordPress repository as Composer packages, no matter if they *natively* support Composer or not.

The other two repositories are Gist of mine. They were created to show in this example how to use custom packages to collect files that you want to share among WP Starter projects. More on this below.

### `"require"` section

There are 3 packages required, and first of them is WP Starter. It is required, of course.

The second is WordPress package. Even if WP Starter requires WordPress, but it is explicitly required to have a better control on the WordPress version that will be installed.

The third package required is `"gmazzap/wpstarter-example-files"`. It is a package I created for this example to show how to use a custom package (in this case saved in a Gist) to collect files to be shared among WP Starter projects. Files contained in this package will be used in the example for various purposes.

### `"require-dev"` section

There are five packages required for development purposes.

First two, "query-monitor" and "debug-bar", are popular plugins for debugging. They are both available in WordPress repository and required via wpackagist.

Third package is ["Laps"](https://github.com/Rarst/laps), a profiler plugin by [@Rarst](http://t.co/dTX2awK3Qv) (Andrey Savchenko) that supports Composer natively and is available on Packagist.

The fourth package is ["plugin-profiler"](https://wordpress.org/plugins/plugin-profiler/), a profiler for plugins, it is available in WordPress repository and required via wpackagist.

By reading its [installation instructions](https://wordpress.org/plugins/plugin-profiler/installation/) you'll see that one of the installation options is to create a MU plugin that loads the regular plugin file.

For the purpose of this example I created a very simple MU plugin that just does that, and that is the fifth package required. It is located in [a Gist](https://gist.github.com/Giuseppe-Mazzapica/9939793dfb2a2361cd0f) listed in `"repositories"` to be accessible to Composer.

This MU plugin will be placed in its own folder inside `mu-plugin` folder, but WordPress will be still capable to load thanks to WP Starter loader.

### `"config"` section

This is a [Composer setting](https://getcomposer.org/doc/04-schema.md#config).

In the example above it is used to tell Composer to create optimized autoloader files and to use a specific vendor folder.

### `"scripts"` section

The first entry in this section adds the method `WCM\WPStarter\Setup::run()` as `"post-install-cmd"` script. (See [Composer docs](https://getcomposer.org/doc/articles/scripts.md)). This is the only mandatory entry.

The second entry adds the same method as `"post-update-cmd"` script. This makes Composer to run again WP Starter routine after any update. The reason is that files copied by WP Starter via "dropins" configuration (see below) may be updated, so the updated file should be copied again in content folder.

The third entry, adds the same method using a custom name. This will never run automatically by Composer, but if there is the need to run WP Starter routine manually, this alias will simplify that task: `composer run wpstarter` is easier to type, to remember and more intuitive of `composer run post-install-cmd`.

### `"extra"` section

`"wordpress-install-dir"` tells Composer where to place WordPress package. This is not a WP Starter configuration, but is defined by [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer). This setting is optional and when not used, "wordpress" is used as default.

`"wordpress-content-dir"` informs WP Starter where the content folder is located. This configuration is optional and when not used, "wp-content" is used as default.

Please note that this option will **not** make Composer to place plugins and themes into the given directory.

This option is required only if the content folder is not the standard one and only if WP Starter needs to know where content folder is located, that is, when the `"dropins"` and/or the `"gitignore"` WP Starter configuration are used.

`"wpstarter"` is the object where take place all the WP Starter configurations. All the available settings are documented in the *"WP Starter Options"* documentation page.

In the example above I used:

- `"dropins"` to tell WP Starter to move two files from their package folder (where Composer placed them) to the project content folder. This is required because WordPress recognize these specific files only if they are placed there.

  `object-cache.php` is a dropin to manage object cache using Memcached PECL extension (it's a slightly modified version of [this file](https://github.com/tollmanz/wordpress-pecl-memcached-object-cache)) and `it_IT.php` is a locale file (italian). Both needs to be placed in content folder to work. WP Starter copies them to content folder from the package I saved in a Gist. (see `require` section above).

- `"prevent-overwrite"` is used to tell WP Starter to don't overwrite some files, specifically, `wp-config.php`, `index.php` and `.gitignore`.

  This is especially useful because WP Starter routine is set to be run after any update (see `"scripts"` section above) so any customization to any of these files that happened between installation and update would be lost if WP Starter is not instructed to not overwrite them.

- `env-example` is used to tell WP Starter which file to copy as `.env.example` in project folder. The source used is the same Gist that contains `it_IT.php` file and is used to show how to use a custom package to collect files that you want to share among WP Starter projects.

- `gitignore` is used to tell WP Starter what to put in the `.gitignore` file that will be created in project folder. The configuration tells to ignore WordPress, content and vendor folders, a set of common to-be-ignored files and a list of three custom entries.

For more details about any of the WP Starter configuration have a look to *"WP Starter Options"* documentation page.

### `"installer-paths"` section

This is a configuration section introduced by [composer/installers](https://github.com/composer/installers).

Using this configuration is possible to tell Composer where to place packages of specific types (see [Composer docs](https://getcomposer.org/doc/faqs/how-do-i-install-a-package-to-a-custom-path-for-my-framework.md)).

This option is necessary here because I want to use `public/content` instead of default `wp-content` as project content folder, that is the installation target folder for plugins and themes.
