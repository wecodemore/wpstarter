<!--
currentMenu: completeexample
title:  Complete Usage Example
-->
# Complete Usage Example

For some projects, out-of-the-box settings for WP Starter may be just fine, others might need more customization. This page contains a usage example that makes use of
all of WP Starter configuration. Explanations are provided further down below after the example.

# A *fully equipped* `composer.json`

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
            "url": "https://gist.github.com/gmazzap/e8c8e4dfc8e65f1903ac.git"
        },
        {
            "type": "vcs",
            "url": "https://gist.github.com/gmazzap/9939793dfb2a2361cd0f.git"
        }
    ],
    "require": {
        "wecodemore/wpstarter": "~2.0",
        "johnpbloch/wordpress-core": "4.7.*@stable",
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

The example above might seem a little scary, but most of the time you won't need such a detailed configuration. E.g. the `composer.json` provided in the *"Quick Start"* docs section is a lot less complex and still perfectly functional.

The only reason I publish such a heavily customized `composer.json` is to demonstrate how WP Starter configuration allows you to exert full control over every aspect of its process, in case this should be required.

All of the configurations used in the example above are explained below, section by section.

### `"name"`, `"description"` and `"type"` sections

These are simple Composer settings. See [official docs](https://getcomposer.org/doc/04-schema.md) to know more.

### `"minimum-stability"` section

By default, Composer filters packages by stability, only installing versions that are tagged as stable.

By using this setting ([see Compose docs](https://getcomposer.org/doc/04-schema.md#minimum-stability)), it is possible to tell Composer to also install packages with different stability flags.

This is necessary in the example because some packages that are used are not stable and are added to project by pulling them from Git repositories.

### `"repositories"` section

This is [a Composer setting](https://getcomposer.org/doc/04-schema.md#repositories) that allows to add custom repositories that will be searched for packages by Composer.

I've added 3 entries.

The first is [wpackagist](https://wpackagist.org). It is a mirror of the official wordpress.org plugin and theme repository as a Composer repository. In short, it allows you to install all the plugins and themes available in the official wordpress.org repository as Composer packages, no matter if they *natively* support Composer or not.

The other two repositories are Gists of mine. They were created for this example to demonstrate how to use custom packages to collect files that you want to share among WP Starter projects. More on this below.

### `"require"` section

There are 3 required packages, and the first one is WP Starter. It is required, of course.

The second is the WordPress package. Even if WP Starter requires WordPress, it is still explicitly required to have a better control over the WordPress version that will be installed.

The third required package is `"gmazzap/wpstarter-example-files"`. It is a package I created for this example to show how to use a custom package (in this case saved as a Gist) to collect files to be shared among WP Starter projects. Files contained in this package will be used in the example for various purposes.

### `"require-dev"` section

There are five packages required for development purposes.

The first two, "query-monitor" and "debug-bar", are popular plugins for debugging. They are both available in the wordpress.org repository and required via wpackagist.

The third package is ["Laps"](https://github.com/Rarst/laps), a profiler plugin by [@Rarst](http://t.co/dTX2awK3Qv) (Andrey Savchenko) that supports Composer natively and is available on Packagist.

The fourth package is ["plugin-profiler"](https://wordpress.org/plugins/plugin-profiler/), a profiler for plugins, it is available in wordpress.org repository and required via wpackagist.

By reading its [installation instructions](https://wordpress.org/plugins/plugin-profiler/installation/) you'll see that one of the installation options is to create a MU plugin that loads the regular plugin file.

For the purpose of this example I created a very simple MU plugin that just does that, and that is the fifth required package. It is located in [a Gist](https://gist.github.com/Giuseppe-Mazzapica/9939793dfb2a2361cd0f) listed in `"repositories"` to be accessible to Composer.

This MU plugin will be placed in its own folder inside `mu-plugin` folder, but WordPress will still be capable to load it thanks to WP Starter MU loader code.

### `"config"` section

This is a [Composer setting](https://getcomposer.org/doc/04-schema.md#config).

In the example above it is used to tell Composer to create optimized autoloader files and to use a specific vendor folder.

### `"scripts"` section

The first entry in this section adds the method `WCM\WPStarter\Setup::run()` as `"post-install-cmd"` script. (See [Composer docs](https://getcomposer.org/doc/articles/scripts.md)). This is the only mandatory entry.

The second entry adds the same method as `"post-update-cmd"` script. This makes Composer run WP Starter routine again after any update. The reason is that files copied by WP Starter via "dropins" configuration (see below) may be updated, so the updated file should be copied into the content folder again.

The third entry adds the same method using a custom name. This will never run automatically by Composer, but if there is the need to run WP Starter routine manually, this alias will simplify that task: `composer run wpstarter` is easier to type, to remember and more intuitive than `composer run post-install-cmd`.

### `"extra"` section

`"wordpress-install-dir"` tells Composer where to place the WordPress package. This is not a WP Starter configuration, but is defined by [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer). This setting is optional and when not used, "wordpress" is used as default.

`"wordpress-content-dir"` informs WP Starter where the content folder is located. This configuration is optional and when not used, "wp-content" is used as default.

Please note that this option will **not** make Composer place plugins and themes into the given directory.

This option is required only if the content folder is not the standard one and only if WP Starter needs to know where the content folder is located, that is, when the `"dropins"` and/or the `"gitignore"` WP Starter configurations are used.

`"wpstarter"` is the object in which all the WP Starter configuration takes place. All the available settings are documented in the *"WP Starter Options"* documentation page.

In the example above I used:

- `"dropins"` to tell WP Starter to move two files from their package folder (where Composer placed them) to the project content folder. This is required because WordPress recognizes these specific files only if they are placed there.

  `object-cache.php` is a dropin to manage an object cache using Memcached PECL extension (it's a slightly modified version of [this file](https://github.com/tollmanz/wordpress-pecl-memcached-object-cache)) and `it_IT.php` is a locale file (italian). Both need to be placed into the content folder to work. WP Starter copies them to the content folder from the package I saved in a Gist. (see `require` section above).

- `"prevent-overwrite"` is used to tell WP Starter to not overwrite some files, specifically `wp-config.php`, `index.php` and `.gitignore`.

  This is especially useful because WP Starter routine is set to be run after any update (see `"scripts"` section above) so any customization to any of these files that happened between installation and update would be lost if WP Starter is not instructed not to overwrite them.

- `env-example` is used to tell WP Starter which file to copy as `.env.example` in project folder. The source used is the same Gist that contains the `it_IT.php` file and is used to show how to use a custom package to collect files that you want to share among WP Starter projects.

- `gitignore` is used to tell WP Starter what to put into the `.gitignore` file that will be created in the project folder. The configuration is set to ignore WordPress, content and vendor folders, a set of commonly ignored files and a list of three custom entries.

For more details about any of the WP Starter configurations have a look at the *"WP Starter Options"* documentation page.

### `"installer-paths"` section

This is a configuration section introduced by [composer/installers](https://github.com/composer/installers).

Using this configuration it is possible to tell Composer where to place packages of specific types (see [Composer docs](https://getcomposer.org/doc/faqs/how-do-i-install-a-package-to-a-custom-path-for-my-framework.md)).

This option is necessary here because I want to use `public/content` instead of default `wp-content` as the project's content folder, that is the target installation folder for plugins and themes.
