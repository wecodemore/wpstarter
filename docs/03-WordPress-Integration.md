---
title: WordPress Integration
nav_order: 3
---

# WordPress Integration
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

## Include WordPress in the project

Considering that WordPress has no official support for Composer, there's also no official way to integrate WordPress with Composer.

These days many people agree to do this by **treating WordPress as a dependency**, like any other dependency.

To date, WordPress does not officially provide a Composer compatible repository of WordPress core (basically having a `composer.json`).

The most used non-official package with Composer support is maintained by [John P. Bloch](https://johnpbloch.com/), that at the moment of writing has almost 4 millions of downloads from [packagist.org](https://packagist.org/packages/johnpbloch/wordpress),  but there's also [roots/wordpress](https://packagist.org/packages/roots/wordpress) which is approaching the millionth download at the moment of writing.

That said, WP Starter does **not** declare any of those packages as a dependency, allowing for the use of custom packages or even bypassing the installation of WordPress entirely.

For example, an alternative way could be to use Composer [repositories](https://getcomposer.org/doc/05-repositories.md) settings to build a custom package using the official zip distribution:

```json
{
  "name": "my-company/my-project",
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "wordpress/wordpress",
        "version": "5.4.2",
        "dist": {
          "url": "https://wordpress.org/wordpress-5.4.2-no-content.zip",
          "type": "zip"
        }
      }
    }
  ],
  "require": {
    "wecodemore/wpstarter": "^3",
    "wordpress/wordpress": "5.4.2"
  }
}
```


The benefit in the example above is that we get a release without default `/wp-content` folder, and so without default themes and plugins, that we don't require in our installation and that might slowdown deploy for no reason. The problem, however, is that we need to update the `repositories` setting every time we want to update WordPress.

Yet more ways to install WordPress could include using a [custom package](https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md), or make use of the [wp-downloader](https://github.com/wecodemore/wp-downloader) Composer plugin.

Please note: if WordPress is **not** installed as Composer package (and that the case when using [wp-downloader](https://github.com/wecodemore/wp-downloader)) then the WP Starter setting `{ "require-wp": false }` must be set or WP Starter will look for WordPress among packages and will fail not finding it.


### Dealing with default content

When WordPress is installed using a core package like the [one](https://packagist.org/packages/johnpbloch/wordpress) from [John P. Bloch](https://johnpbloch.com/), the package comes with default themes (Twenty*) and plugins ("Akismet", "Hello Dolly").

Those are often not used at all, all the more so in WP Starter installations where they are not recognized 
by WordPress, because the content folder is customized to be a separate folder outside the core Wordpress folder.

WP Starter provides an option to register default themes shipped with core packages, so that they can 
be recognized, but more often than not default themes and plugins are just unnecessary.

By using a custom WordPress core package (as shown above) it is possible to not download default themes 
and plugins at all, but that requires to update the repositories settings at every WP update.
A more sustainable approach is to delete default content after it is pulled.

WP Starter offers *custom steps* and *step scripts* to do it (more on the topic in the "*WP Starter Steps*" chapter),
but often a simple bash command as [Composer script](https://getcomposer.org/doc/articles/scripts.md) can do the trick:

```json
{
    "scripts": {
        "post-install-cmd": "rm -rf ./wordpress/wp-content",
        "post-update-cmd": "rm -rf ./wordpress/wp-content"
    }
}
```

The WP Starter scripts way would be:

```php
<?php
namespace MyCompany\MyProject;

use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Util\Locator;

function removeDefaultContent(int $result, Step $step, Locator $locator) {
    $path = $locator->paths()->wp('/wp-content');
    if (is_dir($path)) {
        $locator->composerFilesystem()->removeDirectory($path);
        $locator->io()->writeSuccess("Default WP content removed.");
    }
}
```

plus the configuration:

```json
{
    "extra": {
        "wpstarter": {
            "scripts": {
               "pre-wpstarter": "MyCompany\\MyProject\\removeDefaultContent"
            }
        }
    }
}
```

The latter way surely requires more work, but it works across operative systems and takes into account configuration (in the shell command the path to WordPress folder is hardcoded), making the script reusable.
Plus, it has a nice colored output.

More on WP Starter configuration can be learned in the "*WP Starter Configuration*" chapter, and more on step scripts can be learned in the "*WP Starter Steps*" chapter.



## WP Starter `wp-config` interactions

When WP Starter finishes its job it leaves the project folder ready to be used, but WP Starter code does **not** really interact with WordPress after its job is done.

However, the `wp-config.php` that WP Starter generates it is a bit different than the standard one, and introduces a few things into WordPress that are specific to WP Starter-powered websites.

It is important to say that everything in this section refers to the `wp-config.php` file generated by WP Starter using _default template_, but as better explained in the *"WP Starter Steps"* chapter, WP Starter templates can be customized, and if that happens for the `wp-config.php` template, there's no way to tell if functionalities described below will be there or not.



### Environment

In the *"Environment Variables"* chapter it has been presented how WP Starter sets WordPress constants from env variables. That is done via some code placed in the  `wp-config.php` generated by WP Starter.

Beside the env vars named after WordPress configuration constants there are a few more env variables that have a special meaning for WP Starter WordPress installations.

#### WP_ENVIRONMENT_TYPE / WP_ENV

`WP_ENVIRONMENT_TYPE` is an environment variable supported by WordPress, and can also be used by WP Starter to determine the current application environment, for example "production", "staging", and so on.

Support for `WP_ENVIRONMENT_TYPE` environment variable was added in WordPress core with version 5.5, and WP Starter started using it since then.

Before that, WP Starter used the environment variable `WP_ENV`, and `WORDPRESS_ENV` was used in WP Starter v1.

For backward compatibility reasons, both `WP_ENV` and `WORDPRESS_ENV` are still supported, and in case those are used, WP Starter will _also_ set the `WP_ENVIRONMENT_TYPE` constant to ensure compatibility with WordPress 5.5+.

This means that projects that are already using `WP_ENV` or `WORDPRESS_ENV` can upgrade to latest WP Starter without any need to change environment variables and get support for latest WordPress.

##### WP Starter environment VS WordPress environment

WordPress does not allow arbitrary values for `WP_ENVIRONMENT_TYPE`, in fact it  requires the value of that variable to be one of:

- `"local"`
- `"development"`
- `"staging"`
- `"production"`

Unlike WordPress, WP Starter does not limit environment to specific values, and in the case a value not supported by WordPress is used, to maximize compatibility, WP Starter will try to "map" different values to one of those supported by WP.

For example, setting `WP_ENVIRONMENT_TYPE` env variable to `"develop"` WP Starter will define a `WP_ENVIRONMENT_TYPE` constant having `"development"` as value.

The original `"develop"` value will be available in the `WP_ENV` constant.

In the case WP Starter is not able to map an environment to a value supported by WordPress, the original value will be available in both `WP_ENVIRONMENT_TYPE` and `WP_ENV`, but the WordPress [`wp_get_environment_type`](https://developer.wordpress.org/reference/functions/wp_get_environment_type/)  function will return `"production"` because that is te default value used by WordPress.

Let's clarify with an example. If a project sets an env variable like this:

```bash
WP_ENV=preprod
```

WP Starter will declare:

```php
define('WP_ENVIRONMENT_TYPE', 'staging');
define('WP_ENV', 'preprod');
```

WP Starter was able to map `"preprod"` to `"staging"` that is a value supported by WordPress, and thanks to that, `wp_get_environment_type()` will correctly return `"staging"`.

The "mapping" happens by looking for alias according to the following table:

| `WP_ENVIRONMENT_TYPE `/ `WP_ENV` | WP-supported value |
| -------------------------------- | ------------------ |
| dev                              | development        |
| develop                          | development        |
| stage                            | staging            |
| pre                              | staging            |
| preprod                          | staging            |
| pre-prod                         | staging            |
| pre-production                   | staging            |
| uat                              | staging            |
| test                             | staging            |
| prod                             | production         |
| live                             | production         |

Moreover, if the  `WP_ENVIRONMENT_TYPE` (`WP_ENV` / `WORDPRESS_ENV`) environment variable value _contains_ one of the supported values, it will be mapped to it, for example `"production-1"` will be mapped to `"production"`  or `"uat-us-1"` will be mapped to `"staging"`.

**Please note**: the _WP Starter environment_, used for example to load environment-specific files (see below), will always be what's defined in `WP_ENV`, that is the original value defined in the  `WP_ENVIRONMENT_TYPE` (`WP_ENV` / `WORDPRESS_ENV`) environment variable.

Another example. If a project sets an environment variable like this:

```bash
WP_ENV=something_very_custom
```

WP Starter will declare:

```php
define('WP_ENVIRONMENT_TYPE', 'production');
define('WP_ENV', 'something_very_custom');
```

WP Starter was not able to map `"something_very_custom"` to any of the four environment types supported by WordPress, so stored "production" in `WP_ENVIRONMENT_TYPE` constant, because in any case WordPress would have defaulted to that value when calling `wp_get_environment_type()`.

This is why we suggest referring to the constant `WP_ENV` instead to the `WP_ENVIRONMENT_TYPE` constant or the function `wp_get_environment_type()` when there's the desire (or the need) to use environment types that are not one the four supported by WordPress.

Finally, it must be noted that is possible to use a custom WP Starter-specific environment and a WordPress compatible environment by setting _both_ `WP_ENV` and `WP_ENVIRONMENT_TYPE`. 

For example, having an environment like this:

```bash
WP_ENV=something_very_custom
WP_ENVIRONMENT_TYPE=development
```

WP Starter will declare:

```php
define('WP_ENVIRONMENT_TYPE', 'development');
define('WP_ENV', 'something_very_custom');
```



##### Environment-specific files

After environment variables are loaded (via either _actual_ environment or via env file) WP Starter will look, in the same directory where it looks for "main" env file (by default project root), for two environment-specific files, i.e. whose name depends on the value of `WP_ENV` constant.

They are:

- an **env file** named  **`{$envFile}.{$environment}`**, where `$envFile` is the name of the "main" env file (by default `.env`) and `$environment` is the value of  `WP_ENV`.
- a **PHP file** named like **`{$environment}.php`**, where `$environment` is the value of `WP_ENV` constant.

###### Environment-specific env file

If the environment-specific env file is found, it will be loaded, and all the env variables defined there will be merged with anything already loaded (via either actual environment or via main env file).

Note that environment-specific env file can overwrite variables in main env file, but variables defined in actual environment will always win over variables defined in env files.

###### Environment-specific PHP file

If the environment-specific PHP file is found it is just included. This allows for advanced per-environment settings, e.g. force the enabled status of plugins based on environments and so on.

To make such an advanced configuration that involves WordPress, the `{$environment}.php` file needs to add WordPress hooks, and that *normally* would be not doable from `wp-config.php`, because WordPress hooks functions are not loaded yet, but WP Starter allows that by early loading `plugin.php` (something that can be done with no issue in recent versions of WordPress). More on this below.



##### Default environments

If  WP Starter environment is set to one of:

- `"local"`
- `"development"`
- `"staging"`
- `"production"`

WP Starter will setup WordPress debug-related PHP constants accordingly.

The code that does that looks like this:

```php
switch ($environment) {
    case 'local':
    case 'development':
        defined('WP_DEBUG') or define('WP_DEBUG', true);
        defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', true);
        defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', false);
        defined('SAVEQUERIES') or define('SAVEQUERIES', true);
        defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', true);
        break;
    case 'staging':
        defined('WP_DEBUG') or define('WP_DEBUG', true);
        defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', false);
        defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', true);
        defined('SAVEQUERIES') or define('SAVEQUERIES', false);
        defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', true);
        break;
    case 'production':
    default:
        defined('WP_DEBUG') or define('WP_DEBUG', false);
        defined('WP_DEBUG_DISPLAY') or define('WP_DEBUG_DISPLAY', false);
        defined('WP_DEBUG_LOG') or define('WP_DEBUG_LOG', false);
        defined('SAVEQUERIES') or define('SAVEQUERIES', false);
        defined('SCRIPT_DEBUG') or define('SCRIPT_DEBUG', false);
        break;
}
```

On top of that, if WP Starter environment is `local`, and `WP_LOCAL_DEV` is not defined, it will be defined to `true`.



#### Cached Environment

Parsing the environment and define constants for it can be quite expensive process. Thanks to code placed in `wp-config.php` that runs on "shutdown" the environment is cached in a file named `.env.cached.php` that contains PHP code that declares env variables and defines constants.

Details about this process and a way to prevent this are described in the ["Environment-Variables"](02-Environment-Variables.md) section.



#### WP_HOME

This is not a WP Starter variable, but a standard WordPress configuration constant.

However, unlike WordPress, WP Starter will always make sure it is set.

If no environment variable with that name is found, WP Starter will calculate the home URL by looking at server variables, defaulting to `"localhost"` if even server variables are not found.

This might be fine in many cases, but setting `WP_HOME` is recommended to avoid issues and avoid calculation of the home URL at every request.



#### WP_ADMIN_COLOR

`WP_ADMIN_COLOR` environment variable will make WP Starter add a filter in the generated `wp-config.php` that will force WordPress dashboard to a specific admin color scheme, overriding the user setting.

For example:

```shell
WP_ADMIN_COLOR=ectoplasm
```

will force the current environment to use the "ectoplasm" admin color scheme.

Supported schemes are the ones shipped core ("light", "blue", "coffee", "ectoplasm", "midnight", "ocean", "sunrise") plus any additional color scheme that plugins might have added to WordPress.

Changing admin color per environment helps to recognize visually which is the current environment and avoid, for example, doing in production operations meant for staging.



### Early loading of `plugin.php`

The file `wp-includes/plugin.php` contains WordPress functions for the [plugin API](https://developer.wordpress.org/plugins/hooks/).

In recent WordPress versions this file has been made "independent" from the rest of WordPress, which means that it can be loaded very early allowing to add hooks very early, before the rest of WordPress is loaded.

In the `wp-config.php` that WP Starter generates, the file is loaded early, so that it is possible to add hooks, for example, in the  `{$environment}.php` (see above) or in a dedicated "early hooks file".



### Early hook file

In WP Starter configuration it is possible to set an "early hooks file" loaded very early (right after `{$environment}.php`) that can be used to add callbacks to hooks triggered very early, like for example [`"enable_loading_advanced_cache_dropin"`](https://developer.wordpress.org/reference/hooks/enable_loading_advanced_cache_dropin/) or to set just-in-time WordPress configuration PHP constants.



### HTTPS behind load-balancers

When websites are behind load-balancers the server variable `$_SERVER['HTTPS']` is sometimes not set, even if the website is implementing HTTPS, and because WordPress function [`is_ssl()`](https://developer.wordpress.org/reference/functions/is_ssl/) relies on that server variable it returns `false` in that case, with many side effects.

As quite standard practice, in the above situations, load balancers set the server variable `$_SERVER['HTTP_X_FORWARDED_PROTO']` to `'https'` . So by looking at that value the result of `is_ssl()` could be forced to `true` by forcing  `$_SERVER['HTTPS']` to `'on'`.

WP Starter can do that. To enable this feature it is necessary to set the environment variable **`WP_FORCE_SSL_FORWARDED_PROTO`** to true.

There might be security implications in this, please see the "Make WordPress" ticket [#31288](https://core.trac.wordpress.org/ticket/31288) for details.

The gist of it is that `HTTP_X_FORWARDED_PROTO` is a server variable filled from an a HTTP header (like any `$_SERVER` variable whose name starts with `HTTP_`) which means that it could be set by the client or by a [MITM](https://en.wikipedia.org/wiki/Man-in-the-middle_attack) proxy. On the other hand, environment variable can only be set on the server, so checking the env variable makes the presence of HTTP header trustable.



### Advanced customization of WP Starter `wp-config.php`

All the features described above are applied via using a `wp-config.php` template that comes with WP Starter.

It is possible to have a completely custom template, but sometimes what it is desired is just a small customization, like adding a line of code. Using a custom template for that is not a good approach, because to have the same functionalities it would be necessary to copy the original template code, hence loosing all the improvements and fixes that newer versions of WP Starter could bring to the template.

This is why the WP Starter default `wp-config.php` template supports the concept of "sections".

A section is a portion of the config file delimited with "labels" with braces, for example:

```php
CLEAN_UP : {
    unset($envName, $envLoader, $cacheEnv);
} #@@/CLEAN_UP
```

The above snippet represents the code for a section named _"CLEAN_UP"_.

WP Starter ships an object called `WpConfigSectionEditor` that can be used to edit any of the section by appending, pre-pending, or replacing the section content. This is usually done in a custom step. 

Detailed documentation about custom steps development is documented in the [Custom Steps Development](08-Custom-Steps-Development.md) section, but what's important here is that using a code like this:

```php
/** @var WeCodeMore\WpStarter\Util\WpConfigSectionEditor $editor */
$editor->append('CLEAN_UP', 'define("SOME_CONSTANT", TRUE);');
```

the section snippet above would become:

```php
CLEAN_UP : {
    unset($envName, $envLoader, $cacheEnv);
    define("SOME_CONSTANT", TRUE);
} #@@/CLEAN_UP
```

Besides `WpConfigSectionEditor::append()`, the object also has the `prepend`, `replace`, and `delete` methods, to, respectively, pre-pend, replace and delete the code in the given section.

Please note that the PHP code to append/prepend/replace is not checked at all, so be sure that it is valid PHP code.

An instance of `WpConfigSectionEditor` can be obtained via the `Locator` class, documentation for it is in the [Custom Steps Development](08-Custom-Steps-Development.md) section.



## Admin URL

When using WP Starter, the suggested way to install WordPress is to use a separate folder for core files.

The docs section ["A Commented Sample `composer.json`"](06-A-Commented-Sample-Composer-Json.md) show an example on how to obtain it. 

When that is done, the `admin_url` becomes something like `example.com/wp/wp-admin/` (assuming WordPress is installed in the "/wp" folder).

Might be desirable to "rewrite" this URL to the usual `/wp-admin/` removing the subfolder. This is something that can be obtained via web-server configuration.

An example `.htaccess` file that contains rewrite rules for WordPress projects (compatible with multisite) can be found here: https://github.com/inpsyde/wpstarter-boilerplate/blob/master/templates/.htaccess.example



------

**Next:** [WP Starter Configuration](04-WP-Starter-Configuration.md)

---

- [Environment Variables](02-Environment-Variables.md)
- ***> WordPress Integration***
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [Running WP CLI Commands](07-Running-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [WP Starter Command](10-WP-Starter-Command.md)

