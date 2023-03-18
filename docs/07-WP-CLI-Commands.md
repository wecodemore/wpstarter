---
title: WP CLI Commands
nav_order: 7
---

# WP CLI Commands
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## WP CLI for automation

WP Starter takes care of the file structure of the website and its configuration at filesystem level, however it does nothing, for example, for the database.

If the aim is to automate the complete bootstrap of the website, it is clear that WP Starter is not enough. [WP CLI](https://wp-cli.org/) completes our requirements.

The setup of a website via WP CLI is something that can be done independently of WP Starter. For example, assuming we have a deploy / CI tool that installs Composer dependencies triggering WP Starter, the same deploy / CI tool can take care of running WP CLI commands.

WP Starter writes a `wp-cli.yml` pointing to the correct WP path so that commands don’t need to pass `--path` argument to WP CLI commands.

However, by telling WP Starter to take care of WP CLI commands, WP Starter will also take care of **making sure WP CLI is available**.



## Installation of WP CLI

When WP Starter is configured to run WP CLI commands, before running them it makes sure WP CLI is available:

1. first it parses all Composer-installed packages to see if WP CLI is installed via Composer
2. if not, it checks a WP CLI phar is available on project root
3. if not, download WP CLI phar and checks its integrity via SHA512 checksum provided by WP CLI

This means that WP CLI will always be available and WP Starter will know where to find it and in which form it comes (Composer package or phar), so it will know *how and where* to send commands.

In short, by letting WP Starter run WP CLI commands it is possible to:

- not bother with WP CLI installation
- write commands in a way that is agnostic of how and where WP CLI is available

It is worth mentioning here that there's a configuration value `install-wp-cli` that can be set to `false` preventing WP Starter to ever attempt to download WP CLI phar.

If `install-wp-cli` is set to false and WP Starter does not find WP CLI, any attempt to run WP CLI commands via WP Starter will fail.



## Commands configuration

Now that benefit of using WP Starter for WP CLI commands are known, let’s see how to tell WP Starter what to execute.

### Evaluation of files

One of the ways supported by WP Starter to run WP CLI commands is to evaluate files via [WP CLI `eval-file` command](https://developer.wordpress.org/cli/commands/eval-file/).

This can be done by setting an array of files in the `wp-cli-files` setting:

```json
{
    "wp-cli-files": [
       "./scripts/my-cli-script-1.php", 
       "./scripts/my-cli-script-2.php" 
    ]
}
```

Nice thing about this approach is that paths can be any local path, including files in vendor folder installed via Composer, which means that it is possible to effectively create packages containing WP CLI-evaluated files to be shared across projects.

Those familiar with the `eval-file` command might observe that by only providing the file name there’s no way to control command arguments.

This can be done by passing an array of objects like this:

```json
{
    "wp-cli-files": [
        {
            "file": "./scripts/my-cli-script-1.php",
            "skip-wordpress": true,
            "args": {
                "foo": "bar",
                "bar": "baz"
            }
        }
    ]
}
```

Where `skip-wordpress` is used set the `--skip-wordpress` flag of `eval-file` command, and `args` key will be the arguments passed to the file (that will be placed in the `$args` variable for the file).

### Commands array

One simple way to setup WP CLI commands to run is to just set an array of commands in the `wp-cli-commands` setting. For example:

```json
{
    "wp-cli-commands": [
        "wp core install",
        "wp user create bob bob@example.com --porcelain --send-email"
    ]
}
```

The same array of commands can be placed in a separate JSON file, whose path is then used as the same configuration value:

```json
{
    "wp-cli-commands": "./scripts/wp-cli-commands.json"
}
```

This method is quite simple to use, but not very powerful, for example it does not allow for conditional commands.

One possible solution in some cases might be to use a PHP file that does whatever it needs to do and finally returns an array of commands. For example:

```json
{
    "wp-cli-commands": "./scripts/wp-cli-commands.php"
}
```

where `wp-cli-commands.php` could, for example, look something like this:

```php
<?php

namespace WeCodeMore\WpStarter;

/** @var Util\Locator $locator */
global $locator;

$env = $locator->env();

// If env configuration is invalid or WP is already
// installed, there's nothing to do.
if (
    !$env->read(Util\DbChecker::WPDB_ENV_VALID)
    || $env->read(Util\DbChecker::WP_INSTALLED)
) {
    return [];
}

$commands = [];

// If DB does not exist, let's tell WP CLI to create it.
if (!$env->read(Util\DbChecker::WPDB_EXISTS)) {
    $commands[] = 'wp db create';
}

// Home URL is required.
$home = $env->read('WP_HOME');
if (!filter_var($home, FILTER_VALIDATE_URL)) {
    $locator->io()->writeError('Can not install WP, WP_HOME not provided.');

    return [];
}
$user = $env->read('PROJECT_USERNAME') ?: 'admin';
$host = parse_url($home, PHP_URL_HOST);
$email = $env->read('PROJECT_ADMIN_EMAIL') ?: "{$user}@{$host}";

// Build and add install command.
$install = "wp core install";
$install .= " --title='WP Starter Example' --url={$home}";
$install .= " --admin_user='{$user}' --admin_email='{$email}'";
$commands[] = $install;

// Add commands to set up website language if needed.
$language = $env->read('WP_LANG');
if (is_string($language) && (trim($language) !== '')) {
    $language = trim($language);
    $commands[] = "wp language core install {$language}";
    $commands[] = "wp site switch-language {$language}";
}

return $commands;
```

So the file checks status of DB and WordPress and tell WP CLI to act accordingly: do nothing if the status is unknown or WP is already installed, or otherwise install it, by also creating the database if necessary.

To check database status the file uses three "special" env vars, whose names are stored in
`WeCodeMore\WpStarter\Util\DbChecker` class constants:

- `DbChecker::WPDB_ENV_VALID`
- `DbChecker::WP_INSTALLED`
- `DbChecker::WPDB_EXISTS`

To know more about these variables please refers to the [_"Environment Variables"_ chapter](02-Environment-Variables.md).


### Precedence

In the case both `wp-cli-files` and `wp-cli-commands` settings are used, the former are executed first.



## Running commands via step scripts

Another way to run WP CLI commands is to use step scripts. As described in the chapter *"WP Starter Steps"* step scripts are callbacks executed either before or after each step.

For example, by targeting the script `post-wpstarter` it is possible to add scripts that run WP CLI commands.

The way we can do that is via a "process" object that can be obtained from the `Locator` passed as argument to the script callback.

For example, we can put in `wpstarter.json`:

```json
{
    "scripts": {
        "post-wpcliconfig": "Me\\MyProject\\Script::example"
    }
}
```

and then the class `Me\MyProject\Script` can contain:

```php
<?php
namespace Me\MyProject;
    
use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Util\Locator;

class Script
{
    public static function example(int $result, Step $step, Locator $locator)
    {
        // Note: when using `wpCliProcess()->execute()` don't start command with "wp"
        $locator->wpCliProcess()->execute('cli version');
    }
}
```

For this to work `Me\MyProject\Script` class must be autoloadable.

The same thing can be done by creating a custom step, because the `Locator` (and its `wpCliProcess()` method) is also available for custom steps. However using custom steps or scripts make sense only if there's a complex logic behind the command generation otherwise adding commands `wp-cli-commands` or `wp-cli-files` is definitively easier.



------

**Next:** [Custom Steps Development](08-Custom-Steps-Development.md)

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- ***WP CLI Commands***
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [Command-line Interface](10-Command-Line-Interface.md)
