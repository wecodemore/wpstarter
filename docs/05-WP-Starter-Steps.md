---
title: Steps
nav_order: 5
---

# WP Starter Steps
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## What's a step

At its core, WP Starter is a command-line script that performs, in order, a set of tasks that are called **"steps"**.

In WP starter code, a step is nothing more than a PHP class performing a specific operation.




## Default steps

The (current) list of default WP Starter steps is (in order of execution):

| Slug              | Class Name<sup>1</sup> | Notes                 |
|-------------------|------------------------|-----------------------|
| checkpaths        | `CheckPathStep`        | blocking              |
| wpconfig          | `WpConfigStep`         | create file, blocking |
| index             | `IndexStep`            | create file, blocking |
| flushenvcache     | `FlushEnvCacheStep`    |                       |
| muloader          | `MuLoaderStep`         | create file           |
| envexample        | `EnvExampleStep`       | create file, optional |
| dropins           | `DropinsStep`          |                       |
| movecontent       | `MoveContentStep`      | optional              |
| publishcontentdev | `ContentDevStep`       | optional              |
| vcsignorecheck    | `VcsIgnoreCheckStep`   | create file, optional |
| wpcliconfig       | `WpCliConfigStep`      | create file           |
| wpcli             | `WpCliCommandsStep`    |                       |

<sup>1</sup> All class names are in the namespace `\WeCodeMore\WpStarter\Step`.

### Clarification on "Notes" above

- "*blocking*" indicates a step that in case of failure will prevent WP Starter to proceed with subsequent steps.
- "*create file*" indicates a step could create a file from a template.
- "*optional*" indicates a step that (might) ask the user a confirmation before running



## Customizable templates

Several steps produce files. Files are built using "templates" as a base, where placeholders are replaced with values calculated by the step.

Templates are located in the `/templates` directory under WP Starter root.

However, users might replace all or some templates via the `templates-dir` configuration.

If `templates-dir` configuration is set, any file in it having the same name as the ones in the default templates directory will be used as a replacement for the default templates.

Considering that `templates-dir` could be any folder, and considering that WP Starter always run _after_ Composer install/update, it is possible to have Composer packages containing WP Starter templates and so having templates reusable across projects.



## Steps details

### `CheckPathStep`

This task will check that the WP Starter is able to reach required paths. It will check that WordPress and WP content folder exist and that the Composer autoload file is found.

This ensures that subsequent steps will perform operations in the right paths. Moreover, it ensures that at the moment WP Starter is performing its steps Composer will have finished installing all dependencies.

WP Starter will not proceed with other steps if this one fails.



### `WpConfigStep`

This is the main WP Starter step. It creates a `wp-config.php` that setups WordPress based on environment variables and adds WP Starter specific features as described in the [*"WordPress Integration"*  chapter](03-WordPress-Integration.md).

Just like any other step that builds a file, by overriding the template it is possible to have a completely different outcome. Anything in this documentation refers to the behavior of the file generated with the _default_ template.

The settings involved in this steps are:

- `cache-env` - When false (default is true) this  prevents the environment to be cached in a PHP file and instead always loaded on the fly. See the *"WordPress Integration"* chapter for more details.
- `register-theme-folder` - When true the default themes (those shipped with WordPress package) folder will be registered via [`register_theme_directory`](https://developer.wordpress.org/reference/functions/register_theme_directory/) and so default themes will available in WordPress
- `env-dir` and `env-file` - Via these two settings it is possible to load a different env file instead of the default `.env` located under project root.
- `early-hook-file` - If a file path is provided via this setting WordPress will load the file very early, but after having "manually" loaded `plugin.php` so that it is possible to add callbacks to hooks fired very early. See the *"WordPress Integration"* chapter for more details.
- `env-bootstrap-dir` - A custom directory where to look for environment-specific bootstrap files. Environment-specific bootstrap files are PHP files named after the current environment (set in the `WP_ENVIRONMENT_TYPE` env var) that are loaded very early (right after `plugin.php` is loaded by WP Starter) allowing to fine-tune WordPress for specific environments. See the *"WordPress Integration"* chapter for more details.

Besides these configurations, a few path-related settings in `composer.json` will affect this step as well:

- [`vendor-dir`](https://getcomposer.org/doc/06-config.md#vendor-dir) Composer configuration will affect the step because it specifies where to look for the autoload file
- `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) will tell where WordPress `ABSPATH` is located
- `wordpress-content-dir` will tell where content folder is located. This is used to declare the [`WP_CONTENT_DIR`](https://codex.wordpress.org/Determining_Plugin_and_Content_Directories#Constants) constant so that WordPress can correctly handle a content folder located outside WordPress core folder.

WP Starter will not proceed with other steps if this fails for any reason.

The default `wp-config.php` template used by WP Starter supports "sections", that allow implementors to append/prepend/replace only *parts* of the generated `wp-config.php` without using a custom template. The [*"WordPress Integration"* chapter ](03-WordPress-Integration.md) documents for this feature.



### `IndexStep`

In a typical WP Starter powered installation, WordPress is not installed at the webroot, meaning that WordPress `ABSPATH` (the directory that contains `/wp-includes` and `/wp-admin`) is not the webroot.

This is a fairly common setup even for installations not using WP Starter. There's a codex page that explain the approaches to [give WordPress its own directory.](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory)

As described there, we need to create an `index.php` file located at webroot. This step does exactly that.

The only setting affecting this step is the `wordpress-install-dir` which tells Composer where to place WordPress files and folders.

WP Starter will not proceed with other steps if this fails for any reason.



### `FlushEnvCacheStep`

This step will clear the environment cache file if found. See [*"WordPress Integration"* chapter](03-WordPress-Integration.md) for more info about cached environment.

There are no configurations affecting this step.



### `MuLoaderStep`

MU plugins (aka "*Must-use plugins*") are special single-file plugins that WordPress always executes, in fact, they can't be activated and deactivated like regular plugins.

MU plugins are supported by [Composer Installers](http://composer.github.io/installers/) and so Composer packages containing MU plugins will be correctly installed in the `/mu-plugins` subfolder inside WP content folder.

However, Composer will place each of them in an **own subdirectory**, but unfortunately WordPress is not able to load MU plugins from sub-folders: for WordPress a MU plugin is a single file placed *directly* inside `wp-content/mu-plugins`.

This step creates a MU plugin, placed in `wp-content/mu-plugins` folder, that loads all the MU plugins that Composer placed in its own subfolder.

There's no configuration affecting this step. The MU plugins to load are identified by WP Starter looking at installed Composer packages with [type](https://getcomposer.org/doc/04-schema.md#type) `"wordpress-muplugin"`. If no such packages are installed via Composer the step is skipped.

When a package of type `"wordpress-muplugin"` contains only *one* PHP file in its root folder, it is assumed to be the  MU plugin file, and loaded.

WP Starter also supports Composer packages containing *multiple* MU plugins. In that case, only PHP files having the standard `Plugin Name` header will be loaded.



### `EnvExampleStep`

It is a quite standard practice for applications that support `.env` files to provide a `.env.example` file as a blueprint of the available configurations.

WP Starter ships with a template for such file that includes all the env var names that resemble WP configuration constants and this step copies that file into the project root folder.

The step outcome might actually change based on the `env-example` setting. By setting it to `false`, the step is entirely skipped. Moreover, the step is also skipped if an `.env` file is found, as it makes no sense providing an example for something that exists already.

When `env-example` setting is `true` WP Starter will copy the template in project root, and when the setting is `"ask"` WP Starter will ask the user before copying.

Finally, `env-example` setting can also be a path to copy the example file from, or even a URL from where to download it. This latter is not recommended, because no security check is done on the downloaded file, so make sure at least to point to a trusted server and use HTTPS instead of plain HTTP.



### `DropinsStep`

WordPress supports special files called "dropins" that if placed in the WP content folder are loaded very early and can be used to customize different aspects of WordPress.

It seems there's no official documentation that lists all the available dropins, so the "source of truth" in this case is the source code itself, namely the source code of [`_get_dropins()`](https://developer.wordpress.org/reference/functions/_get_dropins/) function.

According to that function the dropins always supported are:

- `advanced-cache.php`
- `db.php`
- `db-error.php`
- `install.php`
- `maintenance.php`
- `object-cache.php`

plus a few more supported only on multisite installations:

- `sunrise.php`
- `blog-deleted.php`
- `blog-inactive.php`
- `blog-suspended.php`

Documentation can be found online for many of them.

Even if these files are supported by Composer installers, they face the same issue as MU plugins: WordPress will not recognize them in a subfolder.

Unlike for MU plugins, for dropins the issue can't be solved via a "loader", because WordPress only loads specific file names, so the only way to make dropins installable via Composer and also make them recognizable by WordPress is to either **symlink or copy them into the content folder** after the installation.

WP Starter allows to do that in this step. The dropin files WP Starter will symlink or copy are determined in two ways:

- installed Composer packages having `wordpress-dropin` type
- files entered in the `dropins` WP Starter configuration

Dropins to be maintained in the same repository as the project, could use the "content dev" feature (see `ContentDevStep` below). Dropins placed in separate packages might either use the  `wordpress-dropin` package type, or there should be a configuration like:

```json
{
    "extra": {
        "wpstarter": {
            "dropins": {
                "advanced-cache.php": "./vendor/acme/advanced-cache/advanced-cache.php"
            }
        }
    }
}
```

Such configuration make it possible to have a package with a different type that _also_ ships a dropin.

Instead of using a local path, like in the example above, the `dropins` configuration also supports arbitrary URLs as source. That is not recommended, because no security check is done on the downloaded file, so make sure at least to point to a trusted server and use HTTPs instead of plain HTTP.



### `MoveContentStep`

WP Starter assumes that WordPress is installed via Composer, and popular WordPress packages include default themes and plugins ("TwentySomething" themes, "Hello Dolly" plugin).

Because WP Starter normally uses a non-standard WP content folder located outside of WordPress folder, those default themes and plugins are not recognized by WordPress.

The scope of this step is to move the default plugins and themes from the WP package's `/wp-content` folder to the project's content folder, so that WordPress can recognize them.

The main setting affecting this step is `move-content` that can be set to`true` to enable the step. When `false` (default) this step is skipped. The value of the setting can also be *"ask"* and if so WP Starter will ask the user before moving the files.

When the `register-theme-folder` setting is `true` WP Starter will also skip this step because default themes will be available anyway and otherwise a non-existing theme folder would be registered.



### `ContentDevStep`

Often a WP Starter project is made of a `composer.json` and little less, because WordPress "content" packages: plugins, themes, and MU-plugins are pulled from *separate* Composer packages.

However, it happens that project developers want to place project-specific "content" packages in the same repository of the project, because it's not worth having a separate package for them or because being very project specific there's no place to reuse them and consequently no reason to maintain them separately.

One way to do this is to just place those project-specific plugins or themes in the project WP content folder, which is the folder that will make them recognizable by WordPress, but it is also the folder where Composer will place plugins and themes pulled via separate packages.

This introduces complexity in managing VCS, because, most likely the developer wants to avoid keeping Composer managed dependencies under version control, yet surely wants to keep project specific plugins and themes under version control. So, in practice, the content folder can't be entirely Git-ignored (nor entirely disposable).

WP Starter offers a different, totally optional, approach for this issue.

Plugins and themes that are developed in the project repository, can be placed in a dedicated folder and WP Starter will either symlink or copy them to the project WP content folder so that WordPress can find them with no issue.

`ContentDevStep` step is responsible to do exactly that.

In WP Starter, "*development content*" refers to any content such as plugins, themes, dropins, and translations files, that WordPress expects in the "wp-content" folder.

There are two settings that affect how the step works: `content-dev-op` and `content-dev-dir`.

`content-dev-dir` tells WP Starter where to look for "development content" folders. By default it is the `/content-dev` folder under the project root.

So by default, this step will symlink (or copy if symlink fails):

-  `./content-dev/plugins/*` to `./wp-content/plugins/*`
-  `./content-dev/themes/*` to `./wp-content/themes/*`
-  `./content-dev/mu-plugins/*` to `./wp-content/mu-plugins/*`
-  all dropins file in `./content-dev/` to  `./wp-content/`

When the base "source" folder is not found, the step is completely skipped.

The default operation (symlink and copy on failure) can be replaced with `content-dev-op`  option.

`content-dev-op`  can be one of *"auto"* (default), "*symlink*", *"copy"* or *"none"*. The latter means WP Starter will not do anything.

Note for **Windows** users: if symlinking fails, make sure to run the terminal application **as administrator**.



### `VcsIgnoreCheckStep`

When using version control there are two reasons we want to keep some paths out of it:

- files that are dynamically generated
- files that contain sensitive data.

Considering WP Starter deals with both kind of files, this steps attempt to determine the VCS
software in use (Git, Mercurial, SVN) and determine if those files are ignored.

When Git is in use, WP Starter is able to effectively check the content of a `.gitignore` an 
existing file, and outputs an error if it does contain the necessary ignore paths.

When either Git or Mercurial is in use, and no `.gitignore`/`.hgignore` is found, WP Starter can
create one using a "template" file as a base.

In all other cases (VCS not determine or not Git nor Mercurial), WP Starter will output a warning
about the paths that should be ignored. The warning is printed only in "verbose" mode.

There are two configurations that control this step:

- `check-vcs-ignore` which can be a boolean or the string "ask". When `false`, none of the checks
  described above happens. When `ask`, WP Starter will ask if performing the check or not.
  Default is `true`.
- `create-vcs-ignore-file` which can be a boolean or the string "ask". When `false`, WP Starter
  will never attempt to write a  `.gitignore`/`.hgignore` file, even if not found.
  When `ask`, it will ask before creating the file.  Default is `true`.



### `WpCliConfigStep`

This step automatically generates in the project root a [`wp-cli.yml`](https://make.wordpress.org/cli/handbook/config/#config-files) that only contains setting for the WordPress path, allowing WP CLI commands to be run on the project root, without the need to pass the `--path` argument every time (see WP CLI [documentation](https://make.wordpress.org/cli/handbook/config/#global-parameters)).

The only setting that affects this step is the `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) which will tell where WordPress is located.



### `WpCliCommandsStep`

WP Starter provides a way to run WP CLI commands right after WP Starter finishes its work successfully.

There are several different ways to do this without using WP Starter at all. It could even be possible to add WP CLI commands to [Composer scripts](https://getcomposer.org/doc/articles/scripts.md) so that no more than `composer update` would be necessary to execute both WP Starter and WP CLI commands.

However, by adding commands to WP Starter configuration WP Starter will ensure that WP CLI is available on the system.

First of all, WP Starter will check if WP CLI has been required via Composer. If so, it will do nothing, as it is already available. If WP CLI is not found among installed packages, WP Starter looks for a `wp-cli.phar` in project root, and if even that is not found WP Starter will download the WP CLI phar and will verify it using the hash provided by WP CLI.

It means that adding commands to WP Starter configuration requires the same effort as adding them to Composer scripts or to any other automation mechanism, but by using WP Starter it is possible to get installation of WP CLI "for free".

The, [*WP CLI Commands* chapter](07-WP-CLI-Commands.md) describes how to setup WP Starter to run WP CLI commands and which WP Starter settings are involved.



## Add, replace or remove steps to run

By default, WP Starter will run all the steps, even if some of them are skipped during runtime because conditions to run them are not met, for example the WP CLI commands step will not run if there are no commands to be run.

WP Starter allows to customize which step has to be run in different ways:

- by naming steps that should completely be skipped
- by adding new custom steps
- by replacing some default steps with custom ones

### Skipping steps

To skip some steps it is necessary to set the configuration `skip-steps` to an array of **step names** to be skipped, for example:

```json
{
    "skip-steps": [
        "index",
        "wpcli"
    ]
}
```

### Adding custom steps

It is also possible to add completely custom steps. That can be done via the `custom-steps` setting.

It must be a map of unique step names to step classes, for example:

```json
{
    "custom-steps": {
        "custom-step-one": "MyCompany\\MyProject\\StepClassNameOne",
        "custom-step-two": "MyCompany\\MyProject\\StepClassNameTwo"
    }
}
```

For how to actually develop the step class please refer to [*"Custom Steps Development"* chapter](08-Custom-Steps-Development.md).

### Replacing default steps

Replace an existing default step is not different from adding a custom step where the step name matches the name of the default step.

For example:

```json
{
    "custom-steps": {
        "wpconfig": "MyCompany\\MyProject\\WpConfigBuilder"
    }
}
```



## Extending steps via scripts

Before and after each step WP Starter allow users to run "scripts", which are nothing else than PHP callbacks (because of limitation of JSON, only plain PHP functions or class static methods are supported).

The scripts to be run has to be added to the `scripts` WP Starter setting, which must be a map from scripts slugs to an array of fully-qualified callback names.

Slug must be composed with a prefix that is `pre-` (which means "before") or `post-` (which means "after") followed by the target step slug (custom steps works as well).

```json
{
    "scripts": {
        "pre-build-wpconfig": [
            "MyCompany\\MyProject\\Scripts::beforeWpConfigStep",
            "AnotherCompany\\AnotherProject\\beforeWpConfigStep"
        ],
        "post-custom-step-one": [
            "MyCompany\\MyProject\\runAfterCustomStepOne"
        ]
    }
}
```

The function signature for the scripts callback must be:

```php
function (int $result, Step $step, Locator $locator, Composer $composer);
```

Where:

- `$result` is an integer that can be compared with `WeCodeMore\WpStarter\Step\Step` class constants: 
    - `Step::ERROR` - the step failed
    - `Step::SUCCESS` - the step succeeded or was executed
    - `Step::NONE` - the step was not executed/skipped
    
    `$result` is meaningful only for the _post_ scripts. For _pre_ scripts it will always be `Step::NONE`. Please note that any check on this value should be done by a bitmask check and not direct comparison. In fact, it is possible that some "composed" steps, e. g. the "dropins" step, might return an integer equal to `Step::SUCCESS | Step::ERROR` meaning that it *partially* succeeded.
- `$step` is the target step object, that is an instance of `\WeCodeMore\WpStarter\Step\Step`.
- `$locator` is an instance of `WeCodeMore\WpStarter\Util\Locator` an object that provides instances of other objects parts of WP Starter. In the [*"Custom Steps Development"* chapter](08-Custom-Steps-Development.md) there are more details about this object.
- `$composer` is an instance of `Composer\Composer` the main Composer object.

Besides the scripts for the *actual* steps, there are an additional couple of pre/post scripts: `pre-wpstarter` and `post-wpstarter`, that run respectively before any step starts and after all the steps are completed.

For this "special" couple of scripts, the step object passed as a second parameter will be an instance of `WeCodeMore\WpStarter\Step\Steps` that is a sort of "steps runner" which implements `Step` interface as well. This is especially interesting for the `pre-wpstarter` script, because callbacks attached to that script use the passed `Steps` object and call its `Steps::addStep()` / `Steps::removeStep()` methods, adding or removing steps "on the fly".



## Listing commands

The command:

```shell
composer wpstarter --list-steps
```

Does execute nothing, but lists all available steps, including custom, but excluding those disabled in config or explicitly passed using the `--skip` flag.

Can be used in combination with other flags like `--skip`, `--skip-custom`, and `--ignore-skip-config`.

------

**Next:** [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- ***WP Starter Steps***
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [Command-line Interface](10-Command-Line-Interface.md)

