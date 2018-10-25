# Running WP CLI Commands

WP starter takes care of the file structure of the website and its configuration at filesystem level, however it does nothing, for example, for the database.

If our aim is to automate the complete bootstrap of the website, it is clear that WP Starter is not enough. The way to go to complete what we need is surely [WP CLI](https://wp-cli.org/).

The setup of the website via WP CLI is something that can be surely done independently from WP Starter. For example, assuming we have an deploy / CI tool that install Composer dependencies triggering WP Starter, the same deploy / CI tool can take care of running WP CLI commands.

What WP Starter will do anyway is to write a `wp-cli.yml` to point the correct WP path so that commands don’t need to pass `--path` argument to WP CLI commands.

However, by telling WP Starter to take care of WP CLI commands, WP starter will also take care of making sure WP CLI  is  available.



## Installation of WP CLI

When WP Starter is configured to run WP CLI commands, before running them it makes sure WP CLI is available:

1. first it parses all installed packages to see if WP CLI is installed via Composer
2. if not, it checks a WP CLI phar is available on project root
3. if not, download WP CLI phar and checks its integrity via SHA512 checksum provided by WP CLI

Which means that in any case WP CLI will be available and WP Starter will know where to find it and in which form it comes (Composer package or phar), so it will know how and where to send commands.

In short, by letting WP Starter running WP CLI commands it is possible to:

- don’t bother with W CLI installation
- write commands in a way that is agnostic of how and where WP CLI is available



## Commands configuration

Now that benefit of using WP Starter for WP CLI commands are known, let’s see how to tell WP Starter what to execute.

### Evaluation of files

One of the way supported by WP starter to run WP CLI commands is to evaluate files via [WP CLI `eval-file` command](https://developer.wordpress.org/cli/commands/eval-file/).

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

The same array of commands can be placed in a separate JSON file, whose path is then used as same config value:

```json
{
    "wp-cli-commands": "./scripts/wp-cli-commands.json"
}
```

This method is quite simple to use, but not very powerful, for example does not allow to run commands conditionally.

One possible solution in some cases might be to use a PHP files that does whatever needs to do and finally returns an array fo commands. For example:

```json
{
    "wp-cli-commands": "./scripts/wp-cli-commands.php"
}
```

where `wp-cli-commands.php` could look something like this:

```php
<?php
// load env vars from either real environment or .env file
$env = WeCodeMore\WpStarter\Env\WordPressEnvBridge::load();
$host = $env['DB_HOST'];
if (!$host) {
    // Probably no .env file is there yet
    return [];
}

// Try to connect to WP database
$conn = @mysqli_connect($host, $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
// If it fails, probably no DB is there, let's tell WP CLI to create it.
$commands = ($conn && !$conn->connect_error) ? [] : ['wp db create'];
$conn and mysqli_close($conn);

if ($commands) {
  $user = $env['MY_PROJECT_USERNAME'] ?: 'admin';
  $role = 'administrator';
  $commands[] = "wp user create {$user} {$user}@example.com --role={$role}"; 
}

return $commands;
```

So in few lines the file checks if DB is there and if not tell WP CLI to create it and then add an user taking the
username from env variable.

### Precedence

In the case both `wp-cli-files` and `wp-cli-commands` settings are used, the former are executed first.



## Running commands via step scripts

Another way to run WP CLI commands is to use step scripts. As described in the chapter *"WP Starter Steps"* step scripts are callback that are executed either before or after each step.

For example, by targeting the script `post-build-wp-cli-yml` it is possible to add scripts that run WP CLI commands.

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

The same thing can be done by creating a custom step, because the `Locator` (and its `wpCliProcess()` method) is also available for custom steps, however this make sense only if there's a complex logic behind the command generation otherwise adding commands `wp-cli-commands` or `wp-cli-files` is definitively easier.