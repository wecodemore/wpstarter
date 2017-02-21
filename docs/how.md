<!--
currentMenu: how
title: How Does WP Starter Work?
-->
# How Does WP Starter Work?

The WP Starter flow starts with a `composer.json` file and ends with a fully working WordPress site, ready to work with multiple environments.

Only two words sit in-between these two states: `composer install` typed into a console.

If you're installing locally, and want to try and pull the Git history down where possible, so you can make improvements, use `composer install --prefer-source` instead.

WP Starter uses a [Composer `post-install-cmd` script](https://getcomposer.org/doc/articles/scripts.md). This script is run by Composer after all packages have been installed and performs some operations that configure the WordPress site that has just been installed.

## The "Steps"

Every operation performed by WP Starter is called a "step". Some of them are always performed, others are optional.

Below is a list of all the WP Starter steps.

### 1. `wp-config.php` step

This is the *core* WP Starter step. It generates a `wp-config.php` file that:

   - requires **Composer autoload file** (its path is retrieved dynamically from Composer settings)
   - provides an environment-based configuration for WordPress: configuration constants that traditionally go into `wp-config.php` are set within a `.env` file, and then automatically imported from there. This allows multi-environment settings. Three environments, "production", "development" and "staging" are supported out of the box.
   - contains paths and urls settings to work with a `wp-content` folder placed outside of the main WP directory
   - contains the code to register a folder inside the WordPress installation folder as an additional theme folder, which makes it possible to use default themes that are shipped with the WordPress package.
   - contains the code that allows WordPress to load MU plugins located in sub folders
   - contains the code needed to setup WordPress to work from within its own subdirectory, while maintaining top level urls. Paths and urls are dynamically resolved from `composer.json` settings

### 2. `index.php` step

During this step WP Starter generates an `index.php` file which is the first file always loaded for all the frontend pages, and which loads the WordPress environment. Creation of this file is required when WordPress is placed within its own directory to maintain top level urls. The path to be used in this file is dynamically resolved from `composer.json` settings.

### 3. Content folder step

This step is optional, and is disabled by default. It moves the `wp-content` folder shipped with the WordPress package to a project content folder. This step was active by default in version 1 of WP Starter, but starting from v2, instead of moving the content folder, WP Starter registers it as an additional theme folder. It is possible to enable this step in version 2 via `composer.json` settings.

### 4. Dropins step

Dropins are files that can be used to override core components of WordPress. Examples of these files would be `db.php` (used to customize database access) or `advanced-cache.php` (used to customize the WordPress cache system). This category also includes locale files, PHP files named like a WordPress locale, e.g. `en_US.php`, and which are loaded by WordPress when that locale is used.

To make use of these files, they must be placed into the content folder, without sub folders. WP Starter can copy these files into the proper location using as source a path or an url.

### 5. Sample `.env` step

WP Starter provides an environment-based configuration for WordPress. All configuration constants that go into the `wp-config.php` file in a standard WordPress installation are set in a `.env` file.

In addition to standard WordPress constants, WP Starter supports an additional configuration, `WORDPRESS_ENV`, which is used to set the environment to use for all constants, e.g. "development", "production" or "staging".

The well known [Dotenv package](https://github.com/vlucas/phpdotenv) is then used to load all configurations from the `.env` file and register them as WordPress configuration constants. This makes it possible to easily setup multiple WordPress environments with ease.

This step puts a file named `.env.example` into the project folder that contains a template to be used as base for the `.env` file. This step can be disabled and customized using `composer.json` settings.

### 6. `.gitignore` step

When using a version control system (VCS) it is important to keep files containing critical information out of it. For WP Starter installations this mostly means the `.env` file. Moreover, third-party files that are pulled in via Composer should be kept out of version control as well.

This step creates a `.gitignore` file in the project folder that makes the popular Git VCS ignore the `.env` file and all the Composer dependencies (including WordPress package). This step can be disabled and customized using `composer.json` settings.
