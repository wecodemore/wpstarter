# Running WP CLI Commands

WP Starter takes care of the file structure of the website and its configuration at filesystem level, however it does nothing, for example, for the database.

If the aim is to automate the complete bootstrap of the website, it is clear that WP Starter is not enough. The way to go to complete what we need is surely [WP CLI](https://wp-cli.org/).

The setup of the website via WP CLI is something that can be surely done independently from WP Starter. For example, assuming we have an deploy / CI tool that installs Composer dependencies triggering WP Starter, the same deploy / CI tool can take care of running WP CLI commands.

What WP Starter will do anyway is to write a `wp-cli.yml` to point the correct WP path so that commands don’t need to pass `--path` argument to WP CLI commands.

However, by telling WP Starter to take care of WP CLI commands, WP Starter will also take care of **making sure WP CLI is available**.



## Installation of WP CLI

When WP Starter is configured to run WP CLI commands, before running them it makes sure WP CLI is available:

1. first it parses all installed packages to see if WP CLI is installed via Composer
2. if not, it checks a WP CLI phar is available on project root
3. if not, download WP CLI phar and checks its integrity via SHA512 checksum provided by WP CLI

Which means that in any case WP CLI will be available and WP Starter will know where to find it and in which form it comes (Composer package or phar), so it will know *how and where* to send commands.

In short, by letting WP Starter running WP CLI commands it is possible to:

- don’t bother with WP CLI installation
- write commands in a way that is agnostic of how and where WP CLI is available

It worth mentioning here that there's a configuration value `install-wp-cli` that can be set to `false` preventing WP Starter to ever attempt to download WP CLI phar.

In that's the case and if WP Starter does not find WP CLI, any attempt to run WP CLI commands via WP Starter will fail.



## Commands configuration

Now that benefit of using WP Starter for WP CLI commands are known, let’s see how to tell WP Starter what to execute.

### Evaluation of files

One of the way supported by WP Starter to run WP CLI commands is to evaluate files via [WP CLI `eval-file` command](https://developer.wordpress.org/cli/commands/eval-file/).

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

Who is familiar with `eval-file` command might have noticed that by only providing file name there’s no way to control command arguments.

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

The same array of commands can be placed in a separate JSON file, whose path is then used as same configuration value:

```json
{
    "wp-cli-commands": "./scripts/wp-cli-commands.json"
}
```

This method is quite simple to use, but not very powerful, for example does not allow to run commands conditionally.

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

$env = new Env\WordPressEnvBridge();

// If env configuration is invalid nothing to do.
if (!$env->read(Util\DbChecker::WPDB_ENV_VALID)) {
    return [];
}

// If WP already installed, let's just tell WP CLI to check it.
if ($env->read(Util\DbChecker::WP_INSTALLED)) {
    return ['wp db check'];
}

$commands = [];

// If DB does not exist, let's tell WP CLI to create it.
if (!$env->read(Util\DbChecker::WPDB_EXISTS)) {
    $commands[] = 'wp db create';
}

// Build install command.
$user = $env->read('MY_PROJECT_USERNAME') ?: 'admin';
$home = $env->read('WP_HOME');
$siteUrl = $env->read('WP_SITEURL') ?: $home;
$email = "{$user}@" . parse_url($home, PHP_URL_HOST);
$install = "wp core install";
$install .= " --title='WP Starter Example' --url={$home}";
$install .= " --admin_user='{$user}' --admin_email='{$email}'";

// Add install command plus commands to update siteurl option and setup language.
$commands[] = $install;
$commands[] = "wp option update siteurl {$siteUrl}";
$commands[] = 'wp language core install it_IT';
$commands[] = 'wp site switch-language it_IT';

return $commands;
```

So the file checks status of DB and WordPress and tell WP CLI to act accordingly: do nothing if the status is unknown, check the DB if WordPress looks installed, or otherwise install it, by also creating the database if necessary.

To check database status the file uses three "special" env vars, whose names are stored in
`WeCodeMore\WpStarter\Util\DbChecker` class constants:

- `DbChecker::WPDB_ENV_VALID`
- `DbChecker::WP_INSTALLED`
- `DbChecker::WPDB_EXISTS`

To know more about these variables please refers to the _"Environment Variables"_ chapter.


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
        "post-build-wp-cli-yml": "Me\\MyProject\\Script::example"
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
        $locator->wpCliProcess()->execute('wp cli version');
    }
}
```

For this to work `Me\MyProject\Script` class must be autoloadable.

The same thing can be done by creating a custom step, because the `Locator` (and its `wpCliProcess()` method) is also available for custom steps. However using custom steps or scripts make sense only if there's a complex logic behind the command generation otherwise adding commands `wp-cli-commands` or `wp-cli-files` is definitively easier.