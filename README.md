WP Starter
==========

WordPress super-easy Composer bootstrap.

# Automatize Composer + WordPress Bootstrapping Workflow

This is about [Composer](https://getcomposer.org/). And this is about [WordPress](https://wordpress.org/).
Create whole-site Composer packages for WordPress is just the more *sane* way to use the two *things* together.

If you never did that, go read a [dedicated mini-site](http://composer.rarst.net) curated by
 [Andrey Savchenko](http://www.rarst.net/) ([@Rarst](https://twitter.com/Rarst)).
It explains things good and concisely.

# What WP Starter Does

 - automatically generates a `wp-config.php` that
    - requires Composer autoload file (its path is retrieved dynamically from Composer settings)
    - provide an environment-based configuration for WordPress: configuration constants that *traditionally* go in `wp-config.php`
      are set in a `.env` file, and then automatically *imported* from there.
      This allows multi-environment settings. Three environments: "production", "development" and "staging" are supported out of the box.
    - contains path and url settings to work with a `wp-content` folder placed *outside* main WP directory.
    - Optionally moves default themes and plugins from WordPress package `wp-content` folder to project `wp-content` folder. 
 - setups WordPress to work in its own subdirectory, while maintaining top level urls.
   To do that WP Starter generates a `index.php` in the root folder and applies in batch all the steps
   described by [*"Giving WordPress Its Own Directory"* Codex page](http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory).
   The WordPress path to be used in `index.php` is auto-magically resolved from Composer settings
 - includes a MU plugins loader (automatically added to the generated `wp-config.php`).
   This way MU plugins, that *normally* have to be placed in top level MU plugin folder, can be placed in subfolders,
   so being able to have their own `composer.json` file, they can be managed via Composer just like regular plugins.
 - generates and puts in root folder a `.env.example` file that contains a cheat sheet of all the available
   configuration variables for WordPress, but only three of them are required (database settings).
 - optionally generates and puts in root folder a `.gitignore` file that makes Git ignore
    - `.env` file (that contains sensitive information)
    - generated `wp-config.php` that may contains sensitive information as well
    - vendor folder (whose path is generated dynamically based on Composer settings)
    - WordPress folder (whose path is generated dynamically based on Composer settings)
    - a bunch of common-to-ignore files like IDE specific files, OSX and Windows files like `.DS_Store` or `Thumbs.db`
    - default themes and plugins folders
   

# What does it mean?

It means that you start with a single `composer.json` file, you type a single `composer install`
in your console and you get a project directory that has WordPress, all your preferred plugins and
themes and everything is already configured to just work.

I lied. Not everything is configured. After WP Starter finished its work you still need to add -at least-
your database settings to `.env` file, but it will require no more then 30 seconds.

Moreover, thanks to the environment-oriented settings, to have different database or url settings
for e.g. production and development servers will be super easy.
Environment settings are handled via [Dotenv](https://github.com/vlucas/phpdotenv), a well known
library used by a lot of projects, including some very popular ones like Laravel 5.


# What that *magical* `composer.json` should contain?

Nothing special. Two only things make the magic happen:

 - to add `wecodemore/wpstarter` to requirements
 - to add `WCM\WPStarter\Setup::run` as `post-install-cmd` Composer script.
 
If you ever used Composer, the first of the two should not need explanation.
 
The second is explained in [Composer documentation](https://getcomposer.org/doc/articles/scripts.md).
Long story short, you only need to add to your `composer.json`
 
 ```json
"scripts": {
    "post-install-cmd": "WCM\\WPStarter\\Setup::run"
}
 ```
 
# How to use

 1. Install Composer, if not installed yet.
    Instructions for [*nix](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) and
    for [Windows](https://getcomposer.org/doc/00-intro.md#installation-windows).
 2. Write a `composer.json` file with `wecodemore/wpstarter` among requirements and
   `WCM\WPStarter\Setup::run` as `post-install-cmd` Composer script (below an example)
 3. open console and type `composer install`
 4. When installation finishes, you should find a `.env.example` file in the project folder.
    Rename it to `.env` and set there database settings
 5. Drink some coffee, tea or whatever takes your fancy
 
 
# `composer.json` sample

Below there is an example of `composer.json` to be used with WP Starter.

```json
{
    "name": "gmazzap/wpstarter-project-sample",
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

For plugins and themes that support Composer natively, you can just add their package name to the `require` object.

For plugins and themes that do not support Composer natively but are available in WordPress repository
(like WP Super Cache in the example) you can use packages provided by [wpackagist](http://wpackagist.org/).
To do that you need to add wpackagist to `repositories` as shown above.

In the example above there is no WordPress among requirements. It is because WP Starter requires
WordPress by requiring [`johnpbloch/wordpress`](https://github.com/johnpbloch/wordpress).

If you look at [WP Starter `composer.json`](https://github.com/wecodemore/wpstarter) you'll see that
it is required using 3.9 as minimum version.

If you want to force a newer version, just add `johnpbloch/wordpress` to the requirements of your
`composer.json` using the version constraint you prefer.

The `extra` setting is optional, if not used `wordpress` will be used as folder name for WordPress
installation.

The `config.vendor-dir` setting is optional. In the example above is used to have the `vendor` folder 
placed inside `wp-content` folder so that at the end of the installation the folder structure will 
be something like:

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

*(some files in wp dir have been omitted for sake of readability)*

### Custom WordPress Core Packages
 
If you maintain a custom WordPress Composer package, WP Starter is very probably able to work with it.
However, once WordPress doesn't support Composer, and so there is no official package for it,
[the package maintained by John P. Bloch](https://packagist.org/packages/johnpbloch/wordpress) was
added to WP Starter requirements. It means that if you want to use a custom WP core package with WP Starter
you need to add to the `composer.json` of that package a [`replace`](https://getcomposer.org/doc/04-schema.md#replace)
setting pointing `johnpbloch/wordpress`.

That is a not an ideal solution, but with its almost 60.000 installations @johnpbloch package is largely
the most used Composer package for WordPress core. Nevertheless I think that, waiting the day WordPress
will officially support Composer, having a *virtual* WordPress core package would be nice for cases like these.

For the purpose I opened [an issue](https://github.com/johnpbloch/wordpress/issues/5) on johnpbloch/wordpress
 repository in GitHub and I'd really like to know some opinions on the topic. 
 
---

# System Requirements

 - [Composer](https://getcomposer.org/)
 
# License

MIT. See LICENSE file.

# Dependencies

 - John P. Bloch [WordPress package](https://github.com/johnpbloch/wordpress)
 - [Dotenv](https://github.com/vlucas/phpdotenv) by Vance Lucas
 
# Security Issues

If you have identified a security issue, please email **giuseppe.mazzapica [at] gmail.com** and do not
file an issue as they are public.
