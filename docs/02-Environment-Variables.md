# Environment Variables



## The issue with configuration files

Pretty much all applications need configuration. There are different approaches to that. One of the most used is to have configuration files in some data-exchange format (XML, JSON, Yaml, Neon, Toml...).

PHP applications might use PHP files for configuration.

WordPress uses a single PHP file, `wp-config.php` that is used to declare PHP constants with configuration values.

This approach surely has some advantages (speed above all), but also issues. The main problems are two:

- `wp-config.php` will very likely contain "secrets" that should **not** be kept under version control, but being the file required by WordPress it is hard to conciliate the two things.
- `wp-config.php` does not support multiple environments. Meaning that if the same code will be deployed to, for example, a "staging" and a "production" server it will be necessary to have two versions of the file. This is possible using separate VCS "branches" (if the VCS of choice supports them), but then we fall into the previous issue of being forced to keep secrets versioned.

This issue is surely not limited to WordPress.

A modern approach to this issue (see [The Twelve-Factor App](https://12factor.net/)) is to use **environment variables** to save configuration values.



## What are environment variables?

Environment variables (or simply "env vars") are key-value pairs that can be set in the environment, i.e. in the server that runs the application.

For example, those could be set into the webserver ([Apache](https://httpd.apache.org/docs/2.4/env.html), [nginx](http://nginx.org/en/docs/ngx_core_module.html#env)) making the configuration very specific to the server that runs the application.

As additional advantage, env vars can be set "on the fly" acting on the system, e.g. a continuous integration service can set them without touching the code.

Finally, not being code, env vars don't need to be kept under version control, avoiding the issue of having to keep "secrets" under version control.

It is undeniable that the setting of values on the "bare environment" could be quite cumbersome. This is why many applications and tools support "env files".

In the rest of the documentation we will refer to "actual environment" to mean variables set on the server itself, to distinguish from variables set by parsing env files.



## Introducing env files

An env file is nothing else than a shell script file that does not contain any command, but only variables.

```shell
HELLO="Hello"
GREETING="${HELLO} World!"
```

Tools that support such files read them and set values on the environment "on the fly".

By convention those file are often named `.env`.



## PHP and env vars

In PHP there are two functions: [`getevn`](http://php.net/manual/en/function.getenv.php) and [`putenv`](http://php.net/manual/en/function.getenv.php) that allow to, respectively, read and write env vars on the server in a OS-agnostic way.

There's nothing in PHP core that parse env files, but is no surprise that there are different libraries to do that.

WP Starter uses one of these libraries: the **[Symfony Dotenv Component](https://symfony.com/doc/3.4/components/dotenv.html).**



## WP Starter and env vars

WP Starter uses Symfony Dotenv Component to load an `.env` file found in the project root folder of the project (the folder and the file name can be configured, if necessary).

The env vars loaded from the file will never overwrite variables that are set in the actual environment.

Moreover, if the actual environment contains all the variables WP Starter and WordPress need, there's actually no need to load and parse env files, which is **the suggested way to go in production**, for performance reasons.

Configuring WP Starter to **bypass** the loading of env files is accomplished via the **`WPSTARTER_ENV_LOADED`** env variable, which when set tells WP Starter to assume all variables are in the actual environment.

### Important security note about `.env` file

WP Starter loads an `.env` file found in the project root folder, and it is worth noting that if no additional configuration is made, project root is also the folder assumed as webroot for the project.

This is a non-issue in local-only installations, however it can be quite a serious issue on anything that goes online. In fact, an `.env` file inside webroot could expose secrets stored in it (at very minimum DB credentials).

To avoid this issue there are at least three different ways:

- Create a subfolder inside project root and use it as webroot, keeping `.env `file one level above, in the project root. This is the approach that will be shown in the *"A Commented Sample `composer.json`"* chapter
- Configure WP Starter to load  `.env` file from a folder that is not publicly accessible, e.g. the parent folder of the project root (if project root is also webroot). This can be done via the `env-dir` setting. Learn more in the *"WP Starter Configuration"* chapter.
- Don't use env file in production at all, but store env vars in the actual environment. See documentation on how to do it in [Apache](https://httpd.apache.org/docs/2.4/env.html), and [nginx](http://nginx.org/en/docs/ngx_core_module.html#env). Note that Docker supports an `.env` file as well (see [documentation](https://docs.docker.com/compose/environment-variables/)). By setting env vars that way sensitive application configurations would not be publicly accessible.



## Environment variables and WordPress

Although WP Starter loads env vars (no matter if from file or from actual environment), to work properly WordPress still needs PHP constants to be set with configuration.

WP Starter generates a  `wp-config.php` file that reads env variables and declares PHP constants "on the fly" when an env var matching a WP configuration constant is found.

For example, by having an env file like the following:

```shell
DB_NAME=mydb
DB_USER=mydb_user
DB_PASSWORD=mysecret!
```

WP Starter will load it, will set related environment variables and will also **define `DB_NAME`, `DB_USER`, and `DB_PASSWORD` PHP constants** so that WordPress can work properly.

If the same env vars would be set in the actual environment instead of in env file, nothing would change.

Note that **only variables matching WordPress core constants names will be defined as constants**.

If there's a plugin that supports a constant like `"AWESOME_PLUGIN_CONFIG"`, by setting the related env var WP Starter will *not* declare the constant automatically.

So you might need to write code like:

```php
$config = getenv('AWESOME_PLUGIN_CONFIG');
if ($config) {
    define('AWESOME_PLUGIN_CONFIG', $config);
}
```

There are many places in which such code can be placed, for example a MU plugin.

Alternatively WP Starter can be instructed to do something like this automatically.

The way to go is to use an environment variable called `WP_STARTER_ENV_TO_CONST` that needs to contain a list of of comma-separated list of environment variables that needs to be turned into constants:

```shell
WP_STARTER_ENV_TO_CONST=AWESOME_PLUGIN_CONFIG,ANOTHER_VAR,YET_ANOTHER
```

With such env variable set, WP Start will declare constants for the three env variables.

Please note that env variables are always strings, while PHP constants can be any static type. For WordPress constants that is not an issue because WP Starter knows the expected type can cast the value before define the constant. For custom variables, in case the constant needs to have a different type that can be specified using the syntax: `NAME:TYPE`. For example, having env variables like these:

```shell
WP_STARTER_ENV_TO_CONST=AWESOME_PLUGIN_CONFIG:BOOL,ANOTHER_VAR
AWESOME_PLUGIN_CONFIG=true
```

WP Starter will define `AWESOME_PLUGIN_CONFIG` as boolean: `define('AWESOME_PLUGIN_CONFIG', true)`.



## WP Starter specific env vars

As described above, all WordPress configuration constants are natively supported by WP Starter.

Moreover there are a few env variables that have a special meaning for WP Starter.

### DB check env vars

During its bootstrap process, before doing any operation on the system, WP Starter checks if the
environment is already setup for database connection.
If so, WP Starter attempts a connection and launches a very simple SQL command. Thanks to that
it can determine if connection is possible, if the WP DB exists, and if WP is installed.

These information are stored in three env vars whose names are stored in
`WeCodeMore\WpStarter\Util\DbChecker` class constants:

- `DbChecker::WPDB_ENV_VALID` - is non-empty if connection to DB is possible
- `DbChecker::WPDB_EXISTS` - is non-empty if DB exists and is usable
- `DbChecker::WP_INSTALLED` - is non-empty if WordPress is installed

The three env vars are registered in the order they are listed above: if one is non-empty the
previous must be non-empty as well.

Sometime it might be desirable to bypass this WP Starter check and there's a way to accomplish that
via the `skip-db-check` setting.
Learn more about configuration in the _"WP-Starter-Configuration"_ chapter.

### WordPress Configuration

Those are a few env vars that are used in `wp-config.php` that WP Starter generates.
They are documented in the *"WordPress Integration"* documentation chapter.



## Cached Environment

To parse env files and setup environment variables, and then setup WordPress constants based on the environment, is a bit expensive.

Half of the reason is the env file parsing itself (which can be avoided by setting environment variables in the actual environment). The other half is the fact that WP Starter has no way to know which environment variables matching WP constants are set in the environment before trying to access them. Which means that WP Starter needs to loop **all** the possible WordPress constants, and for each of them try to access an environment variable named in the same way and if that is found finally define a constant.

To avoid this overhead at every request, the WP Starter `wp-config.php` registers a function to be run on "shutdown"  that *dumps* the environment variables that have been accessed with success during the first request into a PHP file so that on subsequent requests the environment "bootstrap" will be much faster.

This cache file is saved in the same folder that contains the `.env` file and is named `.env.cached.php`.

However, having a **cached environment means that any change to it will require the cache file to be deleted**. WP Starter does it via one of its steps ("flush-env-cache") so after a Composer install / update or after running WP Starter command the environment cache is always clean.

Of course it is possible to clean env cache on demand by explicitly running the command:

```shell
composer wpstarter flush-env-cache
```

(Read more about running specific steps via `wpstarter` command in the *"WP Starter Command"* chapter)

There are several ways to prevent WP Starter to generate and use cached environment in first place.

- when `WP_ENV` env var described above is `"local"` the cache by default is not created.
- when the **`cache-env`** configuration is `false`, the cache by default is not created.
- there's a WordPress filter `'wpstarter.skip-cache-env'` that can be used to disable the cache creation.

The `'wpstarter.skip.cache-env'` filter is interesting because it allows to disable cache in specific environments by using the **`{$environment}.php`** file described above.
For example, it is possible to skip environment cache in "development" environment having a `development.php` file that contains:

```php
add_filter('wpstarter.skip-cache-env', '__return_true');
```

The filter is executed very late (so could be added in MU plugins, plugins and even themes) and also passes the environment name as second parameter.

For example, to only allow cache in production there a code lie the following can be used:

```php
add_filter('wpstarter.skip-cache-env', function ($skip, $envName) {
    return $skip || $envName !== 'production';
});
```



------

**Next:** [WordPress Integration](03-WordPress-Integration.md)

---

- ***> Environment Variables***
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [Running WP CLI Commands](07-Running-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [WP Starter Command](10-WP-Starter-Command.md)



