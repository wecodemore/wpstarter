<!--
currentMenu: quickstart
title: Quick Start
-->
# Quick Start

Once a system meets WP Starter requirements (PHP 5.3.2+, Composer), installing a fully working, Composer-managed WordPress site involves just 3 steps:

 1. create a `composer.json` file in an empty folder
 2. open a console and type: `composer install`
 3. rename the `.env.example` file created by WP Starter to `.env`, open it with a text editor and set, at the very least, the database settings.

Done :-)

That `composer.json` file should contain:

 - WP Starter
 - `WCM\WPStarter\Setup::run()` set as `"post-install-cmd"` script
 - WordPress package (optional). By explicitly requiring a WordPress package it is possible to choose the WP version to install (min version is WP 3.9+)
 - Any desired theme(s) and plugin(s) (optional)
 - WP Starter configuration in `"extra"` setting (optional)

# A simple `composer.json`

```json
{
    "name": "gmazzap/wpstarter-simple-example",
    "description": "Example project for WordPress + Composer + WP Starter",
    "type": "project",
    "repositories": [
        {
            "type": "composer",
            "url": "https://wpackagist.org"
        }
    ],
    "require": {
        "wecodemore/wpstarter": "~2.0",
        "wpackagist-plugin/wp-super-cache": "*"
    },
    "config": {
        "vendor-dir": "wp-content/vendor"
    },
    "scripts": {
        "post-install-cmd": "WCM\\WPStarter\\Setup::run",
        "post-update-cmd": "WCM\\WPStarter\\Setup::run"
    },
    "extra": {
        "wordpress-install-dir": "wp"
    }
}
```

This is a minimal example, but it is possible to add any plugin or theme.

For plugins and themes that support Composer natively, it is possible to just add their package name to the require object.

For plugins and themes that do not support Composer natively, but are available in official wordpress.org repository (like WP Super Cache in the example) it is possible to use the packages provided by [wpackagist](https://wpackagist.org). To do that, wpackagist has to be added to `"repositories"` setting as shown above.

The `config.vendor-dir` setting is optional. In the example above it is used to have the vendor folder be placed within the `wp-content` folder, so that at the end of the installation the folder structure will be something like:


  - `wp/`
    - `wp-admin/`
    - `wp-includes/`
    - `wp-settings.php`
    - `wp-blog-header.php`
    - `wp-load.php`
    - `wp-login.php`
  - `wp-content/`
    - `themes/`
    - `plugins/`
    - `vendor/`
  - `wp-config.php`
  - `index.php`
  - `.env.example`
  - `.gitignore`

*(some files in wp folder have been omitted for the sake of readability)*


## There's More

The example in this page shows how simple it is to get started with WP Starter. However, by using both general Composer settings and WP Starter specific settings it is possible to do more with WP Starter and also to control and customize any aspect of the WP Starter flow.

The ***"Complete Usage Example"*** doc page contains a `composer.json` example code that uses all of the WP Starter power by making use of all of the configurations available.

The WP Starter flow is what happens when you go from a `composer.json` to a fully functional WordPress site. What happens in-between is that Composer installs all packages and, after that, WP Starter performs different operations to configure the WordPress site.

All these operations are called *steps* and are individually described on the ***"How It Works"*** doc page.

Moreover, the way every WP Starter step works can be configured with settings in the `composer.json` file. The ***"WP Starter Options"*** doc page describes all the available configurations.
