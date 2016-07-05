<!--
currentMenu: options
title: WP Starter Options
-->
# WP Starter Options

A lot of aspects of how WP Starter works can be customized using settings in the project `composer.json` file.

Moreover, it is possible to add or skip some "steps" from the default WP Starter flow.

All the WP Starter options are set using an object named `"wpstarter"` inside the `"extra"` setting.

Below is a full list of all the supported options, their possible values and an explanation of what they do.

## `"prevent-overwrite"`

Different WP Starter steps create or move files from a source to a target folder. This option controls the behavior of WP Starter in case one of the target files already exists.

The possible values are:

 - `true` (boolean) = any existing file is preserved, nothing gets overwritten.
 - `false` (boolean) = any existing file is ignored and overwritten.
 - `"ask"` (string) = WP Starter asks what to do for every target file that already exists.
 - `$array` (array) = an array of files to be preserved. The array should contain the relative path of the files. It is possible to use *wildcard* characters similar to the ones used in functions like `glob`. An example:

```json
{
  "extra": {
      "wpstarter": {
          "prevent-overwrite": [".gitignore", "*.php"]
      }
  }
}
```

The settings above make WP Starter prevent overwriting all the PHP files as well as the `.gitignore` file.

The default behavior of this setting is:

```json
"prevent-overwrite": [".gitignore"]
```

So, by default, if a `.gitignore` file is found in the target folder, it will not be overwritten.


## `"verbosity"`

This setting controls the verbosity of WP Starter output in console. Possible values are:

 - `2` (integer) = WP Starter outputs everything: information, warnings, errors and questions.
 - `1` (integer) = WP Starter outputs only errors and questions.
 - `0` (integer) = WP Starter does not output anything. For any question needing user input, the default value is used without interaction.

`2` is the default behavior.


## `"register-theme-folder"`

WordPress ships with a set of default themes. They are located within the `wp-content` folder inside the WordPress directory. However, when using WP Starter, that folder is not the content folder used for the site, so WordPress can't recognize them.

When this option is enabled (default) WP Starter adds code to the generated `wp-config.php` file that register the folder where the default themes are located as an additional theme directory.

This option has 3 possible values:

- `true` (boolean) = option is enabled, the default themes folder is registered
- `false` (boolean) = option is disabled, the default themes folder is not registered
- `"ask"` (string) = WP Starter asks in console whether the default themes folder should be registered

Default value is `true`.


## `"move-content"`

To solve the default themes issue described above, v1 of WP Starter used to *physically* move files from the `wp-content` folder shipped with WP to the project content folder. The new approach of registering an additional theme folder is preferred now, but the old behavior can be re-enabled by activating this option and deactivating `"register-theme-folder"`. (If both are active, registration takes precedence).

This option has 2 values:

- `true` (boolean) = option is enabled, the default `wp-content` folder is moved to the project content folder
- `false` (boolean) = option is disabled, the default `wp-content` folder is not moved to the project content folder

Default value is `false`.


## "`dropins`"

Dropins are files with a very specific name that can be used to override specific parts of WordPress core. They **must** be placed into the content folder, or WordPress won't use them.

Among the more popular dropins are:

 - `'advanced-cache.php'`, used to override the WordPress cache system
 - `'db.php'`, used to override database handling
 - `'maintenance.php'`, used to show a custom message when WordPress is in maintenance mode
 - `'object-cache.php'`, used to implement a custom object cache system

There are more dropins, and locale files can be considered dropins as well. Locale files are PHP files named after WordPress locales, e.g. `"it_IT.php"` (for Italian locale) or `"de_DE.php"` (for German locale). WordPress loads any of these files found within the content folder when the related locale is used.

WP Starter can be configured via "`dropins`" option to move any dropin or locale file into the project content folder.

The *source* of these files can be:

 - a file path relative to project root
 - an url

Information, warning and tips regarding usage of this kind of WP Starter options are provided in the *"About paths and urls sources"* section below on this page.

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

The settings above cause the `db.php` dropin to be copied into the project content folder (whichever that is) from the folder `wp-content/plugins/wp-db-drivers/wp-content/db.php` that is the path of [WP DB Driver plugin](https://wordpress.org/plugins/wp-db-driver/) available in the wordpress.org official plugin repository.


## "`unknown-dropins`"

If the option `"dropins"` explained right above is used, before copying the file into the content folder, WP Starter makes sure that the required file is a valid dropin recognized by WordPress.

To check locales, WP Starter uses `wordpress.org` API to retrieve a list of locales supported by WordPress. As this requires a connection to an external server, there are tons of reasons why this operation might fail.

This option controls what WP Starter will do when a non-standard or a non-recognized dropin file is required. There are 3 possible values:

 - `true` (boolean) = WP Starter does not perform any check on the required files, it just tries to copy the given source to the given target file
 - `false` (boolean) = WP Starter never copies files that are not recognized as standard dropins. Note that if the connection to `wordpress.org` fails, a valid locale file will not be recognized as such.
 - `"ask"` (string) = WP Starter asks in console whether to copy the non-recognized dropin file or not.

Default behavior is `"ask"`.


## "`env-example`"

A `.env` file is required to use WP Starter sites. By default, WP Starter puts a *template* for this file named `.env.example` into the project folder. Renaming this file to `.env` is a fast way to get a site completely configured within a few seconds after installation.

Using this option it is possible to configure if and how WP Starter should create this file. Possible values are:

 - `true` (boolean) = WP Starter creates the `.env.example` file using the default template
 - `false` (boolean) = WP Starter does not create any `.env.example`
 - `"ask"` (string) = WP Starter asks in console whether the example file should be created or not
 - `$url_or_path` (string) = use a (relative) file path or an url that points to an existing file that will be copied into the project folder as the `.env.example` file. More information about the usage of this WP Starter option is provided in the *"About paths and urls sources"* section below on this page.

Default value is `true`.

It is worth noting that if a `.env` file exists in the project folder WP Starter just skips the creation of the `.env.example` file, no matter which value the option has been set to.


## "`env-file`"

The default environment file name is `.env`. Use this option to change it to something else. Note that you can specify only the file name here, and not the path.


## "`gitignore`"

This option controls if and how WP Starter has to create a `.gitignore` file for the project.

Possible values are:

 - `true` (boolean) = WP Starter creates the `.gitignore` file using the default template that makes Git ignore the files and folders installed via Composer, the `.env` file and the generated `wp-config.php` file
 - `false` (boolean) = WP Starter does not create any `.gitignore` file
 - `"ask"` (string) = WP Starter asks in console whether to create the `.gitignore` file or not
 - `$url_or_path` (string) = use a (relative) file path or an url that point to an existing file that will be copied into the project folder as a `.gitignore` file. More information about the usage of this WP Starter option is provided in the *"About paths and urls sources"* section below on this page.
 - `$object` (object) = use an object to have a fine-grained control on the files that should be included in the `.gitignore` file

   This object can contain the following key/value pairs:

     - key: `"wp"`, values: `true` or `false`. When `true` the WordPress installation path will be ignored. Default `true`
     - key: `"wp-content"`, values: `true` or `false`. When `true` the project content directory will be ignored. Default `true`
     - key: `"vendor"`, values: `true` or `false`. When true the Composer vendor directory will be ignored. Default `true`
     - key: `"common"`, values: `true` or `false`. When true a set of files that usually are kept out of VCS are added to `.gitignore`.
       Among them there are OS-specific files like `Thumbs.db` or `.DS_Store` and files added by popular IDEs like `.idea/` or `*.sublime-workspace`.
     - key: `"custom"`, value: an array of custom entries to be added to `.gitignore`. Need inspiration? Have a look [here](https://github.com/github/gitignore)

An example of the "`gitignore`" option using an object configuration:

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

WordPress, content and vendor folders' paths are dynamically retrieved from the settings in `composer.json`.

Default value for this option is `true`.


# About paths and urls sources

Several WP Starter options accept urls or paths to be used as *source* for a file.

Paths must always be **relative to the project root** folder, the folder which contains the `composer.json` file. Even though relative paths including dots are allowed
e.g. `"../../a/file.php"`, they are discouraged in order to maximize portability and minimize issues.

Urls must return a response with an **HTTP status code equal to 200 and a `"Content-Type"` header set to `"text/plain"`**. Any other type of response will make the download fail.

Moreover, consider that the url content is retrieved using the the curl library. Using a HTTP connection for this sort of thing is not very safe and HTTPS connections will fail if not properly configured.

The suggested way to overcome all of these issues and to enable easy reuse of files among different WP Starter installations is to create dedicated Composer packages.


## Packages for WP Starter files

By creating a Composer package for the files to be used in WP Starter projects, it is possible to add this package to the project's dependencies and then use the path option to move files into the proper place. Composer fully supports private repositories, so there is no need for a package like this to be public in order to be usable.

A simple use of this feature may be a Gist with the desired files, added to the `"repositories"` setting in `composer.json` to make Composer recognize and use these files.

For example, I'll use [this Gist](https://gist.github.com/Giuseppe-Mazzapica/e8c8e4dfc8e65f1903ac) that contains some files and I'll show you how to use them in WP Starter projects.

The Gist contains the following files:

 - `composer.json`, necessary to make Composer recognize the Gist as a package
 - `.env.example`, that will be used to show how to use a Gist as a reusable `.env.example` file among WP Starter projects
 - `it_IT.php`, a sample (empty) locale that will be used to show how to use a Gist as a reusable custom locale file among WP Starter projects
 - `object-cache.php`, a sample file (a slightly modified version of [this file](https://github.com/tollmanz/wordpress-pecl-memcached-object-cache)) that will be used to show how to use a Gist as a reusable custom dropin file among WP Starter projects

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

*(note that the example above is not complete, see "Complete Usage Example" doc section for a complete `composer.json` example)*
