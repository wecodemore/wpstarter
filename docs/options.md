<!--
currentMenu: options
title: WP Starter Options
-->
# WP Starter Options

A lot of aspects of how WP Starter works can be customized using setting in the project `composer.json` file.

Moreover, is possible to add or skip some "steps" from default WP Starter flow.

All the WP Starter options are set using an object named `"wpstarter"` inside the `"extra"` setting.

Below there is the full list of all supported options, their possible values and the explanation of what they do.

## `"prevent-overwrite"`

Different WP Starter steps create or move files form a source to a target folder. This option controls the behavior of WP Starter when any target file already exists.

The possible values are:

 - `true` (boolean), when used any existing file is preserved, no overwrite happen.
 - `false` (boolean), when used any existing file is ignored and overwritten.
 - `"ask"` (string), when used, WP Starter asks a question in console for every target file that already exists.
 - `$array` (array), is possible to use an array of files to be preserved. The array should contain the relative path of the files. Is possible to use *jolly* characters similar to the ones used in functions like `glob`. An example:

```json
{
  "extra": {
      "wpstarter": {
          "prevent-overwite": [".gitignore", "*.php"]
      }
  }
}
```

Settings above make WP Starter prevent the overwrite of all the PHP files and of the `.gitignore` file.

The default behavior of this setting is:

```json
"prevent-overwite": [".gitignore"]
```

So, by default, if a `.gitignore` file is found in target folder it is not overwritten.


## `"verbosity"`

This setting controls the verbosity of WP Starter output in console. Possible values are:

 - `2` (integer), when used WP Starter outputs everything: information, warnings, errors and questions.
 - `1` (integer), when used WP Starter outputs: errors and questions.
 - `0` (integer), when used WP Starter outputs nothing. For any question the default value is used with no interaction.

`2` is the default behavior.


## `"register-theme-folder"`

WordPress ships with a set of default themes. They are located in the `wp-content` folder inside WordPress directory. However, when using WP Starter that folder is not the content folder used for the site, so WordPress can't recognize them.

When this option is enabled (default) WP Starter adds to the generated `wp-config.php` file the code needed to register as additional theme directory the folder where default themes are located.

This option has 3 possible values:

- `true` (boolean), option is enabled, default theme folder is registered
- `false` (boolean), option is disabled, default theme folder is not registered
- `"ask"` (string), when used, WP Starter asks in console if default theme folder has to be registered

Default value is `true`.


## `"move-content"`

To solve the default theme issue described right above, v1 of WP Starter used to *physically* move files from `wp-content` folder shipped with WP to the project content folder. The new approach of additional theme registration is preferred now, but old behavior can be re-enabled by activating this option and deactivating `"register-theme-folder"`. (If both are active registration wins).

This option has 2 values:

- `true` (boolean), option is enabled, default `wp-content` folder is moved to project content folder
- `false` (boolean), option is disabled, default `wp-content` folder is not moved to project content folder

Default value is `false`.


## "`dropins`"

Dropins are specific-named files that can be used to override specific parts of WordPress core. They **must** be placed in content folder, or WordPress can't use them.

Among most popular dropins there are:

 - `'advanced-cache.php'` Used to override WordPress cache system
 - `'db.php'` Used to override database handling
 - `'maintenance.php'` Used to show a custom message when WordPress is in maintenance mode
 - `'object-cache.php'` Used to implement a custom object cache system

There are different other dropins, moreover, locale files can be considered dropins as well. Locale files are PHP files named after WordPress locales, e.g. `"it_IT.php"` (for italian locale) or `"de_DE.php"` (for german locale). WordPress loads any of these files found in content folder when the related locale is used.

WP Starter can be configured via "`dropins`" option to move to project content folder any dropin or locale file.

The *source* of these files can be:

 - a file path relative to project root
 - an url

Informations, warning and and tips regarding usage of this kind of WP Starter options are provided in the *"About paths and urls sources"* section below in this page.

An example:

```json
{
  "extra": {
      "wpstarter": {
          "dropins": {
              "db.php": "wp-content/plugins/wp-db-driver/wp-content/db.php"
          }
      }
  }
}
```

Using settings above the `db.php` dropin is copied to project content folder (whichever it is) from the folder `wp-content/plugins/wp-db-drivers/wp-content/db.php` that is the path of [WP DB Driver plugin](https://wordpress.org/plugins/wp-db-driver/) available in WordPress official plugin repository.


## "`unknown-dropins`"

When the option `"dropins"` explained right above is used, before copying the file to content folder WP Starter checks that the required file is a valid dropin recognized by WordPress.

To check locales, WP Starter uses `wordpress.org` API to retrieve a list of locales supported by WordPress. Once this requires a connection to external server, there are tons of reasons why this operation may fail.

This option controls what WP Starter has to do when a non-standard or a non-recognized dropin file is required. There are 3 possible values:

 - `true` (boolean), when used WP Starter does not perform any check on the required files, just try to copy the given source to given target file
 - `false` (boolean), when used WP Starter never copy files that are not recognized as standard dropins. Note that if connection to `wordpress.org` fails a valid locale file can't be recognized as so.
 - `"ask"` (string) when used WP Starter asks in console if copy the non recognized dropin file or not.

Default behavior is `"ask"`.


## "`env-example`"

A `.env` file is required to use WP Starter sites. By default, WP Starter puts in the project folder a *template* for this file named `.env.example`. Renaming this file to `.env` is a fast way to get site completely configured few seconds after installation.

Using this option is possible to configure if and how WP Starter should create this file. Possible values are:

 - `true` (boolean), WP Starter creates the `.env.example` file using default template
 - `false` (boolean), WP Starter does not create any `.env.example`
 - `"ask"` (string) WP Starter asks in console if the example file should be created or not
 - `$url_or_path` (string) it is possible to use a (relative) file path or an url that points to an existing file that will be copied to project folder as `.env.example` file. More info about the usage of this kind of WP Starter options are provided in the *"About paths and urls sources"* section below in this page.

Default value is `true`.

It worth noting that if a `.env` file exists in project folder WP Starter just skip the creation of `.env.example`, no matter which value the option has.


## "`gitignore`"

This option controls if and how WP Starter has to create a `.gitignore` file for the project.

Possible values are:

 - `true` (boolean), WP Starter creates the `.gitignore` file using default template that makes Git ignore files and folder installed via Composer, `.env` file and the generated `wp-config.php`
 - `false` (boolean), WP Starter does not create any `.gitignore` file
 - `"ask"` (string) WP Starter asks in console if the `.gitignore` file should be created or not
 - `$url_or_path` (string) it is possible to use a (relative) file path or an url that point to an existing file that will be copied to project folder as `.gitignore` file. More info about the usage of this kind of WP Starter options are provided in the *"About paths and urls sources"* section below in this page.
 - `$object` (object) it is possible to use an object to have a fine-grained control on the files that should be included in the `.gitignore` file.

   This object can contain following key/value pairs:

     - key: `"wp"`, values: `true` or `false`. When `true` WordPress installation path will be ignored. Default `true`
     - key: `"wp-content"`, values: `true` or `false`. When `true` project content directory will be ignored. Default `true`
     - key: `"vendor"`, values: `true` or `false`. When true Composer vendor directory will be ignored. Default `true`
     - key: `"common"`, values: `true` or `false`. When true a set of files that usually are kept out of VCS are added to `.gitignore`.
       Among them there are OS-specific files like `Thumbs.db` or `.DS_Store` and files added by popular IDEs like `.idea/` or `*.sublime-workspace`.
     - key: `"custom"`, value: an array of custom entries to be added to `.gitignore`. Needs inspiration? Have a look [here](https://github.com/github/gitignore)

An example of "`gitignore`" option using object configuration:

```json
{
  "extra": {
      "wpstarter": {
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
      }
  }
}
```

WordPress, content and vendor folders path are dynamically retrieved from settings in `composer.json`.

Default value for this option is `true`.


# About paths and urls sources

Several WP Starter options accept urls or paths to be used as *source* for a file.

Paths must always be **relative to project root** folder that is the folder which contains the `composer.json` file. Even if relative paths including dots are allowed
e.g. `"../../a/file.php"` they are discouraged to maximize portability and minimize issues.

Urls must return a response with **HTTP status code equal to 200 and `"Content-Type"` header set to `"text/plain"`**. Any different kind of response will make download fail.

Moreover, consider that to retrieve url content is used the curl library. Using HTTP connection for this sort of things is not very safe and HTTPS connection will fail if not properly configured.

The suggested way to overcome all this issues and enforce the possibility of easily reuse files among different WP Starter installations, is to create dedicated Composer packages.


## Packages for WP Starter files

By creating a Composer package for the files to be used in WP Starter projects, is possible to add it to project dependencies and then using path option to move files in proper place. Composer fully support private repositories, so there is no need that a package like that have to be public to be used.

A simple usage of this feature may be a Gist with the desired files, added to `"repositories"` setting in `composer.json` to make Composer recognize and use that files.

For example, I'll use [this Gist](https://gist.github.com/Giuseppe-Mazzapica/e8c8e4dfc8e65f1903ac) that contains some files and I'll show how to use them in WP Starter projects.

The Gist contains following files:

 - `composer.json`, necessary to make Composer recognize the Gist as a package
 - `.env.example`, that will be used to show how to use Gist for reuse `.env.example` file among WP Starter projects
 - `it_IT.php`, a sample (empty) that will be used to show how to use Gist for reuse custom locale files among WP Starter projects
 - `object-cache.php`, a sample file (a slightly modified version of [this file](https://github.com/tollmanz/wordpress-pecl-memcached-object-cache)) that will be used to show how to use Gist for reuse custom dropin files among WP Starter projects

The `composer.json` settings to use the Gist in any WP Starter project will be something like this:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://gist.github.com/Giuseppe-Mazzapica/e8c8e4dfc8e65f1903ac.git"
        }
    ],
    "require": {
        "gmazzap/wpstarter-example-files": "*",
        "wecodemore/wpstarter": "~2.0"
    },
    "extra": {
        "wpstarter": {
            "dropins": {
                "it_IT.php": "vendor/gmazzap/wpstarter-example-files/it_IT.php",
                "object-cache.php": "vendor/gmazzap/wpstarter-example-files/object-cache.php"
            },
            "env-example": "vendor/gmazzap/wpstarter-example-files/.env.example",
            "gitignore": "vendor/gmazzap/wpstarter-example-files/.env.example"
        }
    }
}
```

*(note that example above is not complete, see "Complete Usage Example" doc section for a complete `composer.json` example)*
