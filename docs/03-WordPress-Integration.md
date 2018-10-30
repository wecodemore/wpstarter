# WordPress Integration

## Include WordPress in the project

Considering that WordPress has no official support for Composer, there's also no official way to integrate WordPress with Composer.

The way these days many people agree to do it is to **treat WordPress as a dependency**, like the others. And because WordPress, at this day, does not provide a repository of WordPress with support for Composer (basically having a `composer.json`) the most used package for the scope is the non-official package maintained by [John P. Bloch](https://johnpbloch.com/), that at the moment of writing has around 2.5 millions of downloads from [packagist.org](https://packagist.org/packages/johnpbloch/wordpress).

That said, WP Starter does **not** declare that package has a dependency, allowing to use custom packages or event to don't install WordPress via Composer at all.

For example, one possible alternative way could be to use Composer [repositories](https://getcomposer.org/doc/05-repositories.md) settings to build a custom package using the official zip distribution:

```json
{
  "name": "my-company/my-project",
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "wordpress/wordpress",
        "version": "4.9.8",
        "dist": {
          "url": "https://wordpress.org/wordpress-4.9.8-no-content.zip",
          "type": "zip"
        }
      }
    }
  ],
  "require": {
    "wecodemore/wpstarter": "^3",
    "wordpress/wordpress": "4.9.8"
  }
}
```

The benefit here is that we get a release without default `/wp-content` folder, and so default themes and plugins, that might be not used in our installation. The problem is that we need to update the `repositories` setting every time we want to update WordPress.

Yet more ways to install WordPress could include using a [custom package](https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md), or make use of the [wp-downloader](https://github.com/wecodemore/wp-downloader) Composer plugin.

### Dealing with default content

When WordPress is installed using a core package like the [one](https://packagist.org/packages/johnpbloch/wordpress) from [John P. Bloch](https://johnpbloch.com/), the package comes with default themes (Twenty*) and plugins ("Akismet", "Hello Dolly").

Those are often not used at all, even because in WP Starter installations they are not recognized by WordPress, because the content folder is customized to be a separate folder outside core folder.

WP Starer provides an option to register default themes shipped with core package, so they can be recognized, but more often than not default themes and plugins are just unnecessary.

By using a custom WordPress core package (as shown above) it is possible to don't download default themes and plugins at all, but that requires to update the repositories settings at every WP update. A more sustainable approach is to delete default content after it is pulled.

WP Starter offers *custom steps* and *step scripts* to do it (more on the topic in the "*WP Starter Steps*"  chapter), but often a simple bash command [Composer script](https://getcomposer.org/doc/articles/scripts.md) can do the trick:

```json
{
    "scripts": {
        "post-install-cmd": "rm -rf ./wordpress/wp-content",
        "post-update-cmd": "rm -rf ./wordpress/wp-content"
    }
}
```

The equivalent using WP Starter scripts would be:

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

This latest way surely requires more work, but it works across operative systems and takes into account configuration (in the shell command the path to WordPress folder is hardcoded), making the script reusable.

More on WP Starter configuration can be learned in the "*WP Starter Configuration*" chapter, and more on step scripts can be learned in the "*WP Starter Steps*" chapter.



## WP Starter `wp-config` interactions

When WP Starter finishes its job it leaves the project folder ready to be used, but WP Starter code does **not** really interacts with WordPress after its job is done.

However, the `wp-config.php` WP Starter generates it is a bit different than the standard one, and introduces a few things into WordPress that are specific to WP Starter-powered websites.



### Environment

In the *"Environment Variables"* chapter has been presented how WP Starter sets WordPress constants from env variables. That is done via some code placed in the  `wp-config.php` generated by WP Starter.

Besides the env vars named after WordPress configuration constants there are a few that have a special meaning for WP Starter WordPress installations.

#### WP_ENV

If **`WP_ENV`** variable is set to either `"development"`, `"staging"` or `"production"` WP Starter will setup WordPress debug-related PHP constants accordingly.

The code that does it looks like this:

```php
switch (getenv('WP_ENV')) {
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

For backward compatibility reason, instead of `WP_ENV` it is possible to use `WORDPRESS_ENV` with same result.

But there's more. If a PHP file named like `{$environment}.php` (where `$environment` is the value of `WP_ENV` env var) exists in project root it will be loaded in the related environment.

This allows for more advanced per-environment settings, e.g. force the enabled status of plugins based on environments and so on.

To make such advanced configuration that involves WordPress, the `{$environment}.php` file needs to add WordPress hooks, and that *normally* would be not doable from `wp-config.php`, because WordPress hooks functions are not loaded yet, but WP Starter allows that by early loading `plugin.php` (something that can be done with no issue in recent versions of WordPress). More on this below.

#### WP_ADMIN_COLOR

`WP_ADMIN_COLOR` environment variable will make WP Starter add a filter in the generated `wp-config.php` that will force WordPress dashboard to a specific admin color scheme, overriding the user setting.

For example:

```shell
WP_ADMIN_COLOR=ectoplasm
```

will force the current environment to use the "ectoplasm" color scheme.

Supported schemes are the ones shipped core ("light", "blue", "coffee", "ectoplasm", "midnight", "ocean", "sunrise") plus any additional color scheme that plugins might have added to WordPress.

Changing admin color per environment helps to recognize visually which is the current environment and avoid, for example, doing in production operations meant for staging.



### Early loading of `plugin.php`

The file `wp-includes/plugin.php` contains WordPress function for [plugin API](https://developer.wordpress.org/plugins/hooks/).

In recent WordPress versions this file has been made "independent" from the rest of WordPress, which means that it can be loaded very early allowing to add hooks very early, before the rest of WordPress is loaded.

In the `wp-config.php` WP Starter generates the file is loaded early, so that it is possible to add hooks, for example, in the  `{$environment}.php` (see above) or in a dedicated "early hooks file".



### Early hook file

In WP Starter configuration it is possible to set an "early hooks file" loaded very early (right after   `{$environment}.php`) that can be used to add callbacks to hooks triggered very early, like for example [`"enable_loading_advanced_cache_dropin"`](https://developer.wordpress.org/reference/hooks/enable_loading_advanced_cache_dropin/) or to set just-in-time WordPress configuration PHP constants.



### HTTPs behind load-balancers

When websites are behind load-balancers the server variable `$_SERVER['HTTPS']` is sometimes not set, even if the website is implementing HTTPs, and because WordPress function [`is_ssl()`](https://developer.wordpress.org/reference/functions/is_ssl/) relies on that server variable it returns `false` in that case, with many side effects.

As quite standard practice, in those cases load balancers set the server variable `$_SERVER['HTTP_X_FORWARDED_PROTO']` to `'https'` . So by looking at that value the result of `is_ssl()` could be forced to `true` by forcing  `$_SERVER['HTTPS']` to `'on'`.

WP Starter can do that. To enable this feature it is necessary to set the environment variable **`WP_FORCE_SSL_FORWARDED_PROTO`** to true.

There might be security implications in this, please see the "Make WordPress" ticket [#31288](https://core.trac.wordpress.org/ticket/31288) for details.

The gist of it is that `HTTP_X_FORWARDED_PROTO` is a server variable filled from an a HTTP header (like any `$_SERVER` variable whose name starts with `HTTP_`) which means that it could be set by the client or by a [MITM](https://en.wikipedia.org/wiki/Man-in-the-middle_attack) proxy. On the other hand, environment variable can only be set on the server, so checking the env variable makes the presence of HTTP header trustable.