---
title: Custom Steps
nav_order: 8
---

# Custom Steps Development
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## Why custom steps?

It may often be desirable to perform custom operations to automate the setup of websites.

A "natural" solution would be to use shell scripts or simple PHP scripts for this scope, however when something beyond the most trivial tasks is required, such solutions become more complex to write,  maintain and reuse.

Moreover, if we write custom steps integrated in WP Starter (and so also in Composer) we are able to:

- access Composer configuration;
- access WP Starter configuration;
- access tools provided by WP Starter;
- easily make the custom step configurable.



## The interface

A custom WP Starter step is nothing more than a class implementing `WeCodeMore\WpStarter\Step\Step` interface, that is quite simple having just four methods:

```php
interface Step
{
    const ERROR = 1;
    const SUCCESS = 2;
    const NONE = 4;
    
    public function name(): string;
    
    public function success(): string;
    
    public function error(): string;
    
    public function allowed(Config $config, Paths $paths): bool;
    
    public function run(Config $config, Paths $paths): int;
}
```

- `name()` has just to return a name for the step. This should be with all lowercase and without spaces or special characters. Doing so it would be possible to use the WP Starter command to programmatically run this step.
- `success()` and `error()` has to return feedback message for the user in case the command execution was either successful or not.
- `allowed() ` must return true if the step should be run. For example, a step that will copy a file will return false in this method if the source file is not found.
- `run()` is where the actual execution of the step happen. This method is never called if `allowed() ` returns false, so if some checks are done in that method, there's no need to perform those again or to manually call `allowed()` inside `run()`.
  The method should return one the interface constants `Step::SUCCESS` or `Step::ERROR` depending on the step being successful or not. `Step::NONE` should be returned if the step is aborted for any reason without an outcome that can be either considered successful or erroneous.

The first three methods are very simple and pretty much logicless.

The last two methods receive the same parameters. Those are objects that provide information about WP Starter and Composer configuration to inform the methods' logic.

### Config object

The `Config` object (fully qualified name `WeCodeMore\WpStarter\Config\Config`) is an object that provides access to all the WP Starter settings, those that are set in the `extra.wpstarter` object in `composer.json` or in the file `wpstarter.json` (see *"WP Starter Configuration"* chapter).

The object implements `ArrayAccess` to access settings, where the keys to obtain values are the exact same keys used in the JSON config files.

Accessing a value does **not** return the "plain" value as it is set in the JSON files, but returns an instance of a `Result` object. This is a wrapper that helps to avoid exceptions in case of missing configuration and provides helpers to get or check the "wrapped" value.

The most relevant methods of `Result` object are:

- `unwrapOrFallback()` - returns the wrapped value or in case of error or missing value, any fallback passed as argument, or `null`. 
- `unwrap()` - returns the wrapped value but throws an exception in case of error (e.g. if the value set in configuration files is not compatible with the expected format).
- `notEmpty()` - returns true if the value contains a non-error, non-null value.
- `is()` - compares the passed argument with the wrapped value and returns true in case they match.
- `not()`  - compares the passed argument with the wrapped value and returns true in case they don't match.
- `either()` - compares all the passed variadic arguments with the wrapped value and returns true in case the wrapped value is one of the passed arguments.

### Paths object

The `Paths` object (fully qualified name `WeCodeMore\WpStarter\Util\Paths`) is an object wrapping the configuration for all relevant folders of the project.

It has several methods, each for one path:

- `root()` - returns the project root folder
- `vendor()` - returns the project Composer vendor folder, according to configuration in `composer.json` 
- `bin()` - returns the project Composer in folder, according to configuration in `composer.json` 
- `wp()` - returns the project WordPress folder, according to configuration in `composer.json` . This equals to `ABSPATH` when in WordPress context.
- `wpParent()` - returns the path where `wp-config.php` is saved.
- `wpContent()` - returns the project WordPress content folder (which contains plugin, themes...), according to configuration in `composer.json`.
- `template()` - takes a `$filename` argument, and returns the full path of given file, searching in the WP Starter templates folder that can be customised in `composer.json` or `wpstarter.json`.

**Each** of the methods accepts a relative path to be appended. For example, if `$paths->wpContent()` returns `/html/my-project/wp-content/` , than  `$paths->wpContent('plugins/plugin.php')`  will return `/html/my-content/plugins/plugin.php` . And so on.



## Making steps classes autoloadable

When creating custom steps or even extending steps via scripts it is necessary that step classes and scripts callbacks are autoloadable, or it is not possible for WP Starter to run them.

The obvious way to do that it is to use entries via the [`autoload`](https://getcomposer.org/doc/01-basic-usage.md#autoloading) setting in `composer.json`. That obviously works, but considering that Composer is used to require WordPress, and that Composer's autoload is loaded at every WordPress request, "polluting" Composer autoload with things that are not meant to be run in production is probably not a good idea considering that the autoloader can have quite a substantial impact on web request time.

WP Starter itself registers a custom autoloader just in time before running its steps, and only registers  in `composer.json` autoload the minimum required, that is its plugin class and little more.

WP Starter also offers to users the possibility to require a PHP file before running WP Starter steps. This file can then be used to manually require files, declare functions, or register autoloaders.

WP Starter will also look for a file named, by default, `"wpstarter-autoload.php"` in project root, but the path can be configured using the **`autoload`** setting.

For example in `wpstarter.json`:

```json
{
    "autoload": "./utils/functions.php",
    "scripts": {
        "pre-wpstarter": "MyCompany\\MyProject\\sayHelloBeforeStarting"
    }
}
```

and in `utils/functions.php` inside project root:

```php
<?php
namespace MyCompany\MyProject;

use WeCodeMore\WpStarter\Step\Step;
use WeCodeMore\WpStarter\Util\Locator;

function sayHelloBeforeStarting(int $result, Step $step, Locator $locator) {
    $locator->io()->writeCenteredColorBlock('magenta', 'black', "Hello there!\n");
}
```

An even more powerful yet simpler way to obtain autoload for custom steps is via WP Starter extensions.



## WP Starter extensions

Many configurations of WP Starter allow pointing local files, which includes files in packages pulled via Composer. Which means that creating Composer packages to extend WP Starter possibilities is an effective way to, for example, add  shared configuration, WP CLI scripts, custom scripts, and custom steps.

In such packages, especially in custom scripts and steps, it is very likely necessary to make use of WP Starter objects, which means that WP Starter should probably be used as a dependency.

However, when installing (or updating) Composer packages that declares WP Starter as a dependency, right after Composer ends installation (or update), WP Starter will set in, trying to run its steps.

A special **package type**, **`wpstarter-extension`** , can be used in such packages to avoid that issue: when WP Starter will recognize that root package has `wpstarter-extension` type, will do nothing on Composer installation or update.

Moreover, packages of type `wpstarter-extension` can use the setting `extra.wpstarter-autoload` to set up an autoload strategy that will only be used when WP Starter perform its tasks, without affecting regular application autoload.

### WP Starter autoload

The setting `wpstarter-autoload` inside the `extra` setting of packages of type `wpstarter-extension` is modeled on `autoload` setting of Composer, but it supports a subset of the Composer autoload types.

In fact, it supports only:

- `psr-4`
- `files`

For example, a package with a `composer.json` like this:

```json
{
    "name": "my-company/my-wpstarter-extension",
    "type": "wpstarter-extension",
    "require": {
        "wecodemore/wpstarter": "^3"
    },
    "extra": {
        "wpstarter-autoload": {
            "files": [
                "helpers.php"
            ],
            "psr-4": {
                "MyCompany\\WpStarterExtension\\": "src/"
            }
        }
    }
}
```

will make the `helpers.php` file in package root to be loaded right before WP Starter runs its steps, plus any PSR-4 compatible class inside its `src/` folder will be available for use.



## An example step

A good way to explain how to build something is by examples. Here we provide an example on **how to write a custom step for creating a `.htaccess` file** in webroot.

The step will integrate with WP Starter configuration and will accept user input via CLI if necessary.

### The basics

Let's start by creating the class file and implement the interface.

```php
namespace WPStarter\Examples;

use WeCodeMore\WpStarter\Step\FileCreationStep;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;

class HtaccessStep implements FileCreationStep {
    
    public function name(): string
    {
        return 'build-htaccess';
    }
    
    public function success(): string
    {
        return '.htaccess creates successfully.';
    }
    
    public function error(): string
    {
        return '.htaccess creation failed.';
    }
    
    public function targetPath(Paths $paths): string
    {
        return $paths->wpParent('.htaccess');
    }
    
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }
    
    public function run(Config $config, Paths $paths): int
    {
        // TODO
    }
}
```

First of all, let's notice how the interface implemented is not `Step`, but `FileCreationStep`: another interface provided by WP Starter that has to be implemented by steps that create files, and that's our case.

That interface extends `Step` by only adding the `targetPath` method that has to return the full path where the created file will be saved.

Thanks to that method WP Starter will check if the file exists before even attempting to create it and will try to overwrite it only if the `prevent-overwrite` WP Starter setting permit so. For example, if `prevent-overwrite` is set to `"ask"` WP Starter will ask the user a confirmation before overwriting the existing file, or if `prevent-overwrite` is explicitly set to not  overwrite `.htaccess` the entire step will be skipped at all.

Considering that WP Starter will check for us that any overwrite will happen with respect to user settings, we don't really have reasons to not run this step. Which means that `allowed()` method can just return `true`.

Finally, it's time to build the step routine, that is the `run()` method.

What we need to do, basically, is to write a file. We could surely use plain PHP functions to do it, however WP Starter ships with a class `Filesystem` that makes the job easier by addressing edge cases, nicely handling errors and so on.

To obtain an instance of this object we can use the WP Starter `Locator` object that is always passed as first argument to step classes constructors.

```php
namespace WPStarter\Examples;

use WeCodeMore\WpStarter\Step\FileCreationStepInterface;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\Locator;

class HtaccessStep implements FileCreationStepInterface {
    
    private $filesystem;
    
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
    }
    
    // ...
}
```

Now, we need to know the content of the file. For readability's sake we extract the file content output in a separate private method. Something like this:

```php
    // ...

    public function run(Config $config, Paths $paths): int
    {
        $content = $this->fileContent();
        if ($this->filesystem->save($content, $this->targetPath($paths))) {
            return Step::SUCCESS;
        }
        
        return Step::ERROR;
    }

    private function fileContent(): string
    {
        $content = <<<'HTACCESS'
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(wp-(content|admin|includes).*) wordpress/$1 [L]
RewriteRule ^(.*\.php)$ wordpress/$1 [L]
RewriteRule . index.php [L]
</IfModule>        
HTACCESS;
        
        return $content;
    }

	// ...
```

And we are done... Or not?

Looking at the content of the `.htaccess` it is possible to notice the following two lines:

```
RewriteRule ^(wp-(content|admin|includes).*) wordpress/$1 [L]
RewriteRule ^(.*\.php)$ wordpress/$1 [L]
```

We are telling Apache that when a URL like `example.com/wp-admin/` is requested, it should be rewritten to `example.com/wordpress/wp-admin/` this way, even if we have WordPress installed into the `/wordpress` subfolder, we can access WP dashboard without having to add the subfolder name into the URL.

This is nice, however the `/wordpress` subfolder is hardcoded here, but that it is just the default folder, that could be configured in `composer.json` to something different.

Maybe in the project we are targeting now, we are just using the default, so the step code is fine as is, but if we aim to reuse this step it worth to adjust it to take into account user settings.

Basically we need to know the relative path from the path where the `.htaccess` will be placed to the WordPress path. And we need to take into account the case in which WordPress is placed in root (it is discouraged but can be done via configuration).

To calculate relative paths, there is a  `Filesystem` object provided by Composer (which is not the same as WP Starter `Filesystem` object) that has a method `findShortestPath` that calculates the relative path between two absolute paths provided as argument.

Such object can also  be obtained via the `Locator`:

```php
namespace WPStarter\Examples;

use WeCodeMore\WpStarter\Step\FileCreationStepInterface;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;
use WeCodeMore\WpStarter\Util\Locator;

class HtaccessStep implements FileCreationStepInterface {
    
    private $filesystem;
    private $composerFilesystem;
    
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
    }
    
    // ...
}
```

Now let's add a bit of logic in our run method to calculate the relative path:

```php
    // ...

    public function run(Config $config, Paths $paths): int
    {
        $from = $paths->wpParent('/');
        $to = $paths->wp('/');
        $relative = $from === $to
            ? ''
            : $this->composerFilesystem->findShortestPath($from, $to, true);
        
        $content = $this->fileContent($relative);
            
        if ($this->filesystem->save($content, $this->targetPath($paths))) {
            return Step::SUCCESS;
        }
        
        return Step::ERROR;
    }

    // ...
```

So we are calculating the relative path, and passing it to `fileContent()` method. When the two paths are the same, relative path is just an empty string.

Finally, in the  `fileContent()` method we can use the relative path passed as argument to dynamically build the file content:

```php
    // ...

    private function fileContent(string $relative): string
    {
        $start = <<<'HTACCESS'
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]      
HTACCESS;
        
        $end = <<<'HTACCESS'
RewriteRule . index.php [L]
</IfModule>      
HTACCESS;
        
        if (!$relative) {
            return $start . $end;
        }
        
        $middle = <<<'HTACCESS'
RewriteRule ^(wp-(content|admin|includes).*) %1$s/$1 [L]
RewriteRule ^(.*\.php)$ %1$s/$1 [L]
</IfModule>        
HTACCESS;
        
        return $start . sprintf($content, $relative) . $end;
    }

	// ...
```

This time we are really done. We have built a flexible step that can be reused in many projects and will adapt the output according to settings.

What's left to do is to add the step to `custom-steps` configuration in `extra.wpstarter` or `wpstarter.json`:

```json
{
    "custom-steps": {
        "build-htaccess": "WPStarter\\Examples\\HtaccessStep"
    }
}
```

and also to make the class autoloadable, via the [`autoload`](https://getcomposer.org/doc/01-basic-usage.md#autoloading) setting in `composer.json` or via the `autoload` WP Starter file (See *"WP Starter Steps"* chapter for more info about the latter). 

Note how the slug in the `"custom-steps"` configuration matches the string returned by step object `name()` method.

For the record, this is the whole class code we have written:

```php
namespace WPStarter\Examples;

use WeCodeMore\WpStarter\Step\FileCreationStepInterface;
use WeCodeMore\WpStarter\Config\Config;
use WeCodeMore\WpStarter\Util\Paths;

class HtaccessStep implements FileCreationStepInterface {

    private $filesystem;
    private $composerFilesystem;
    
    public function __construct(Locator $locator)
    {
        $this->filesystem = $locator->filesystem();
        $this->composerFilesystem = $locator->composerFilesystem();
    }
    
    public function name(): string
    {
        return 'build-htaccess';
    }
    
    public function success(): string
    {
        return '.htaccess creates successfully.';
    }
    
    public function error(): string
    {
        return '.htaccess creation failed.';
    }
    
    public function targetPath(Paths $paths): string
    {
        return $paths->wpParent('.htaccess');
    }
    
    public function allowed(Config $config, Paths $paths): bool
    {
        return true;
    }
    
    public function run(Config $config, Paths $paths): int
    {
        $from = $paths->wpParent('/');
        $to = $paths->wp('/');
        $relative = $from === $to
            ? ''
            : $this->composerFilesystem->findShortestPath($from, $to, true);
        
        $content = $this->fileContent($relative);
            
        if ($this->filesystem->save($content, $this->targetPath($paths))) {
            return Step::SUCCESS;
        }
        
        return Step::ERROR;
    }
    
    private function fileContent(string $relative): string
    {
        $start = <<<'HTACCESS'
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]      
HTACCESS;
        
        $end = <<<'HTACCESS'
RewriteRule . index.php [L]
</IfModule>      
HTACCESS;
        
        if (!$relative) {
            return $start . $end;
        }
        
        $middle = <<<'HTACCESS'
RewriteRule ^(wp-(content|admin|includes).*) %1$s/$1 [L]
RewriteRule ^(.*\.php)$ %1$s/$1 [L]
</IfModule>        
HTACCESS;
        
        return $start . sprintf($content, $relative) . $end;
    }
}
```

With a pretty simple class we have implemented a flexible behavior that integrates with configuration and with WP Starter workflow (for example avoiding to overwrite, or asking for it, based on project settings).

For the ones who want to explore further, WP Starter also ships a `FileContentBuilder` object (obtained from the `Locater` via its `fileContentBuilder()` method) that can render the content of a file from a "template" file that contains placeholders and a set variables to fill them.

By using that object in combination with custom template folders that WP Starters supports via its `templates-dir` setting (or by `Paths::useCustomTemplatesDir()` method), it would be possible to make the step even more flexible while also making the step class code more readable, more "elegant", and quite reduced in size not having to deal with `.htaccess` file content inside the class code.



------

**Next:** [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- ***Custom Steps Development***
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [Command-line Interface](10-Command-Line-Interface.md)
