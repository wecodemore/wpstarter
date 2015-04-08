<!--
currentMenu: quickstart
title: Quick Start
-->
# Quick Start

After a system has WP Starter requirements (PHP 5.3.2+, Composer) to install a fully working, Composer-managed WordPress site involves just 3 steps:

 1. create a `composer.json` file in an empty folder
 2. open a console and type: `composer install`
 3. rename the `.env.example` file created by WP Starter to `.env`, open it with a text editor and set, at least, database settings.

Done :-)

That `composer.json` file should contain:

 - WP Starter
 - `WCM\WPStarter\Setup::run()` set as `"post-install-cmd"` script
 - WordPress package (optional) By explicitly requiring WordPress package is possible to choose WP version to install (min version is WP 3.9+)
 - Any desired theme and plugin (optional)
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
            "url": "http://wpackagist.org"
        }
    ],
    "require": {
        "wecodemore/wpstarter": "~1.1",
        "wpackagist-plugin/wp-super-cache": "*"
    },
    "config": {
        "vendor-dir": "wp-content/vendor"
    },
    "scripts": {
        "post-install-cmd": "WCM\\WPStarter\\Setup::run"
    },
    "extra": {
        "wordpress-install-dir": "wp"
    }
}
```

This is a minimal example, but is possible to add any plugin or theme.

For plugins and themes that support Composer natively, it is possible to just add their package name to the require object.

For plugins and themes that do not support Composer natively, but are available in WordPress repository (like WP Super Cache in the example) is possible to use packages provided by [wpackagist](http://wpackagist.org). To do that, wpackagist has to be added to `"repositories"` setting as shown above.

The `config.vendor-dir` setting is optional. In the example above is used to have the vendor folder placed inside `wp-content` folder so that at the end of the installation the folder structure will be something like:


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

*(some files in wp folder have been omitted for sake of readability)*


## There's More

The example in this page shows how simple is to get started with WP Starter. However, by using both general Composer settings and WP Starter specific settings is possible to do more with WP Starter and also to control and customize any aspect of WP Starter flow.

The ***"Complete Usage Example"*** doc page contains a `composer.json` example code that uses all WP Starter power by making use of all the configurations available.

WP Starter flow is what goes from a `composer.json` to a fully functional WordPress site. What happen in between is that Composer install all packages and, after that, WP Starter performs different operations to configure the WordPress site.

All these operations are called *steps* and are described one by one in the ***"How It Works"*** doc page.

Moreover, the way every WP Starter step works can be configured with settings in the `composer.json` file. The ***"WP Starter Options"*** doc page describes all the available configuration.
