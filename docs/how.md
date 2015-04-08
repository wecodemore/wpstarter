<!--
currentMenu: how
title: How WP Starter Works?
-->
# How WP Starter Works

The WP Starter flow starts with a `composer.json` file and ends with a fully working WordPress site, ready to work with multiple environments.

In the middle there are just and only two words: `composer install` typed in a console.

WP Starter uses a [Composer `post-install-cmd` script](https://getcomposer.org/doc/articles/scripts.md). That script is ran by Composer after all packages have been installed and performs some operations that configure the just installed WordPress site.

## The "Steps"

Every operation performed by WP Starter is called "step". Some of them are always performed, other are optional.

Below there is the list of all WP Starter steps.

### 1. `wp-config.php` step

This is the *core* WP Starter step. It generates a `wp-config.php` that:

   - requires **Composer autoload file** (its path is retrieved dynamically from Composer settings)
   - provides an environment-based configuration for WordPress: configuration constants that traditionally go in `wp-config.php` are set in a `.env` file, and then automatically imported from there. This allows multi-environment settings. Three environments: "production", "development" and "staging" are supported out of the box.
   - contains paths and urls settings to work with a `wp-content` folder placed outside main WP directory
   - contains the code to register theme folder inside WordPress installation folder as additional theme folder, in this way is possible to use default themes that are shipped with WordPress package.
   - contains the code that allows WordPress to load MU plugins located in sub folders
   - contains the code needed to setup WordPress to work in its own subdirectory, while maintaining top level urls. Paths and urls are dynamically resolved from `composer.json` settings

### 2. `index.php` step

During this step WP Starter generates an `index.php` that is the first file always loaded for all the frontend pages, and that loads WordPress environment. Creation of this file is required when WordPress is placed in its own directory to maintain top level urls. The path to be used in this file is dynamically resolved from `composer.json` settings.

### 3. Content folder step

This step is optional, and by default it is disabled. It moves the `wp-content` folder shipped with WordPress package to project content folder. This step was active by default in version 1 of WP Starter, but starting from v2, instead of moving the content folder, WP Starter registers it as additional theme folder. It is possible to enable this step in version 2 via `composer.json` settings.

### 4. Dropins step

Dropins are files that can be used to override core components of WordPress. Example of these files are `db.php` (used to customize database access), `advanced-cache.php` (used to customize WordPress cache system) and different others. In this category may be included locale files, PHP files named like a WordPress locale, e.g. `en_US.php`, that are loaded by WordPress when that locale is used.

To make use of these files, they must be placed in the content folder, without sub folders. WP Starter can copy this files in the proper location using as source a path or an url.

### 5. Sample `.env` step

WP Starter provides an environment-based configuration for WordPress. All configuration constants that in standard WordPress installations go in `wp-config.php` are set in a `.env` file.

In addition to standard WordPress constants, WP Starter supports an additional configuration: `WORDPRESS_ENV` that is used to set the environment to which all constants refers, e.g. "development", "production" or "staging".

The well known [Dotenv package](https://github.com/vlucas/phpdotenv) is then used to load all configurations from the `.env` file and register them as WordPress configuration constants. In this way is possible to easily setup multiple WordPress environments with easy.

This step puts in project folder a file named `.env.example` that contains a template to be used as base for `.env` file. This step can be disabled and customized using `composer.json` settings.

### 6. `.gitignore` step

When using a version control system (VCS) it is important to keep out from it files that contains critical information. For WP Starter installations it mostly means the `.env` file. Moreover, files that are managed via Composer should be keep out of version control as well.

This step creates in project folder a `.gitignore` file that makes the popular Git VCS ignore `.env` file and all the Composer dependencies (including WordPress package). This step can be disabled and customized using `composer.json` settings.
