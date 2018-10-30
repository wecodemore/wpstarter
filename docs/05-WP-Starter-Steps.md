# WP Starter Steps

WP Starter is at its core a command line script that performs, in order, a set of tasks that are called **"steps"**.

A step is nothing more than a PHP class performing a given specific operation.



## Default steps

The (current) list of default WP Starter steps is (in order of execution):

| Slug                | Class Name<sup>1</sup> | Notes                 |
| ------------------- | ---------------------- | --------------------- |
| check-paths         | `CheckPathStep`        | blocking              |
| build-wp-config     | `WpConfigStep`         | create file, blocking |
| build-index         | `IndexStep`            | create file, blocking |
| build-mu-loader     | `MuLoaderStep`         | create file           |
| build-env-example   | `EnvExampleStep`       | create file, optional |
| dropins             | `DropinsStep`          |                       |
| move-content        | `MoveContentStep`      | optional              |
| publish-content-dev | `ContentDevStep`       | optional              |
| build-wp-cli-yml    | `WpCliConfigStep`      | create file           |
| wp-cli              | `WpCliCommandsStep`    |                       |

<sup>1</sup> All class names are in the namespace `\WeCodeMore\WpStarter\Step`

### Clarification on "Notes" above

- "*blocking*" indicates a step that in case of failure will prevent WP Starter to proceed with subsequent steps.
- "*create file*"  indicates a file will create a file from a template.
- "*optional*" indicates a step that (might) ask the user a confirmation before running



## Customizable templates

Several steps produces files. Files are built using "templates" as base, where placeholders in the format `{{{PLACEHOLDER}}}` are replaced with values calculated by the step.

Templates are located in the `/templates` directory under WP Starter root.

However, users might replace all or some templates via the `templates-dir` configuration.

If that setting is filled and files with same name of the ones in the default templates directory are found in the given directory, those will be used instead of the standard ones.

Considering that `templates-dir` could be any folder, and considering that WP Starter always run _after_ Composer install/update, it is possible to have Composer packages containing WP Starter templates and so having reusable templates to use in different projects.



## Steps details

### `CheckPathStep`

This task will check that the WP Starter is able to reach required paths. It will check that WordPress and WP content folder exists and that the Composer autoload file is found.

This ensures that subsequent steps will perform operation in right paths. Moreover, it ensures that at the moment WP Starter is performing its steps Composer have finished installing all dependencies.

WP Starter will not proceed with other steps if this fails.

### `WpConfigStep`

This is the main WP Starter step. It creates a `wp-config.php` that setups WordPress based on environment variables and adds WP Starter specific features as described in the *"WordPress Integration"* chapter.

Just like any other step that builds a file, by overriding the template it is possible to have a completely different outcome, so what is being described in this documentation is the behavior of the file generated with _default_ template.

The settings involved in this steps are:

- `register-theme-folder` - When true the default themes (those shipped with WordPress package) folder will be registered via [`register_theme_directory`](https://developer.wordpress.org/reference/functions/register_theme_directory/) and so default themes will available in WordPress
- `env-dir` and `env-file` - Via these two settings it is possible to load a different env file instead of the default `.env` located under project root.
- `early-hook-file` - If a file path is provided via this setting WordPress will load the file very early, but after having "manually" loaded `plugin.php` so that it is possible to add callbacks to hooks fired very early. See the *"WordPress Integration"* chapter for more details.
- `env-bootstrap-dir` - A custom directory where to look for environment-specific bootstrap files. Environment-specific bootstrap files are PHP files named after the current environment (set in the `WP_ENV` env var) that are loaded very early (right after  `plugin.php` is loaded by WP Starter) allowing to fine-tune WordPress for specific environments. See the *"WordPress Integration"* chapter for more details.

Besides of these configuration, few path-related settings in `composer.json` will affect this step as well:

- [`vendor-dir`](https://getcomposer.org/doc/06-config.md#vendor-dir)Composer configuration will affect the step because it will tell where to look for autoload file
- `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) will tell where WordPress `ABSPATH` is located
- `wordpress-content-dir` will tell where content folder is located. This is used to declare the [`WP_CONTENT_DIR`](https://codex.wordpress.org/Determining_Plugin_and_Content_Directories#Constants) constant so that WordPress can correctly handle a content folder located outside WordPress core folder.

WP Starter will not proceed with other steps if this fails for any reason.

### `IndexStep`

In a typical WP Starter powered installation, WordPress is not installed at the webroot, meaning that WordPress `ABSPATH` (the folder that contains `/wp-includes` and `/wp-admin`) is not the webroot.

This is a fairly common setup even for installation not using WP Starter. There's a codex page that explain the approaches to [give WordPress its own directory.](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory)

As described there, we need to create an `index.php` file located at webroot. This step does exactly that.

The only setting affecting this step is the `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) which will tells Composer where to place WordPress files and folders.

WP Starter will not proceed with other steps if this fails for any reason.

### `MuLoaderStep`

MU plugins (aka "Must-use plugins") are special single-file plugins that WordPress always execute, in fact, they can't be activated and deactivated like regular plugins.

MU plugins are supported by [Composer Installers](http://composer.github.io/installers/) and so Composer packages containing a MU plugin will be correctly installed in the `/mu-plugins` subfolder inside WP content folder.

However, Composer will place each of them in an **own subdirectory**, but unfortunately WordPress is not able to load MU plugins from subfolders: for WordPress a MU plugin is a single file placed *directly* inside `wp-content/mu-plugins`.

This step creates a MU plugin, placed in `wp-content/mu-plugins` folder that loads all the MU plugins that Composer placed in own subfolder.

There's no configuration affecting this step. The MU plugins to load are identified by WP Starter looking at installed Composer packages with [type](https://getcomposer.org/doc/04-schema.md#type) `"wordpress-muplugin"`.

If no MU plugin packages are installed via Composer, the step is entirely skipped.

### `EnvExampleStep`

It is a quite standard practice for applications that support `.env` files to provide a `.env.example` file as a blueprint of the available configurations.

WP Starter ships with a template for such file that includes all the env var names that resemble WP configuration constants and what this step does is to copy that file in project root folder.

The step outcome might actually change based on the `env-example` setting. By setting it to `false`, the step is entirely skipped. Moreover, the step is also skipped if an `.env` file is found, as it makes no sense providing an example for something that exists yet.

When `env-example` setting is `true` WP Starter will copy the template in project root, and when the setting is `"ask"` WP Starter will ask user before copying.

Finally, `env-example` setting can also be a path to copy the example file from, or even URL from where to download it. This latest case it is not very recommended, because no security check is done on the downloaded file, so make sure at least to point a trusted server and use an HTTPS instead of plain HTTP.

## `DropinsStep`

WordPress support special files called "dropins" that if placed in WP content folder are loaded very early and can be used to customize different aspects of WordPress.

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

Even if these files are supported by Composer installers, the same issue of MU plugin happens for them: WordPress will not recognize them from subfolder.

Unlike for MU plugins, for dropins the issue can't be solved via a "loader", because WordPress only loads specific file names, so the only way to make dropins installable via Composer and also make them recognizable by WordPress is to either **symlink or copy them in content folder** after the installation.

WP Starter, via this step, allows to do that.

The main configuration involved is `dropins` which is an array of files to copy.

Besides local paths (which includes files pulled as part of Composer packages in vendor dir) the step is also capable to use arbitrary URLs as source. This latest case it is not very recommended, because no security check is done on the downloaded file, so make sure at least to point a trusted server and use an HTTPS instead of plain HTTP.

An additional configuration that affect the step is `unknown-dropins`. By setting this to `true` WP Starter will ignore the list of supported dropins from WordPress and just copy every file provided in the `dropins` setting to WP content folder. The default is `false`, because setting this to true, in combination of usage of URLs as source might be a security issue. The setting could also have the value of "ask" and in that case WP Starter will ask confirmation to user before copying a file not recognized as dropin.

### `MoveContentStep`

WP Starter assumes that WordPress is installed via Composer, and popular WordPress packages include default themes and plugins (Twenty* themes, "Hello Dolly" and "Akismet" plugins).

Because WP Starter normally uses a non-standard WP content folder located outside of WordPress folder, those default themes and plugins are not recognized by WordPress.

The scope of this step is to move the default plugins and themes from the `/wp-content` folder to the project content folder, so that WordPress will recognize them.

The main setting affecting this step is `move-content` that can be set to`true` to enable the step. When `false` (default) this step is skipped at all. The value of the setting can also be *"ask"* and if so WP Starter will aks user before moving the files.

When the `register-theme-folder` setting is `true` WP Starter will also skip this step because default themes will be available anyway and otherwise a non-existing theme folder would be registered.

### `ContentDevStep`

Often a WP Starter project is made of a `composer.json` and little less, because WordPress "content" packages: plugins, themes, and MU-plugins are pulled from *separate* Composer packages.

However, it happens that project developers want to place project-specific "content" packages in the same repository of the project, because it does not worth to have a separate package for them or because being very project specific there's no place to reuse them and consequently no reason to maintain them separately.

One way to do this is to just place those project-specific plugins or themes in the project WP content folder, which is the folder that will make them recognizable by WordPress, but it is also the folder where Composer will place plugins and themes pulled via separate packages.

This introduces complexity in managing VCS, because, very likely the developer does not want to keep Composer dependencies under version control, but surely wants to keep under version control plugins and themes belonging in the project. So, in practice, the content folder can't be entirely Git-ignored (nor entirely disposable).

WP Starter offers a different, totally optional, approach for this issue.

Plugins and themes that are developed in the project repository, can be placed in a dedicated folder and WP Starter will either symlink or copy them to project WP content folder so that WordPress can find them with no issue.

`ContentDevStep` step is responsible to do exactly that.

There are two settings that affects how the step works: `content-dev-op` and `content-dev-dir`.

`content-dev-op`  can be one of *"symlink"* (default), *"copy"* or *"none"* and it tells WP Starter what to do with the "development content" (that is plugin and themes developed in the project repository).

`content-dev-dir` tells WP Starter where to look for development content folders. By default it is `/content-dev` folder under project root.

So by default, this step will symlink:

-  `./content-dev/plugins/*` to `./wp-content/plugins/*`
-  `./content-dev/themes/*` to `./wp-content/themes/*`
-  `./content-dev/mu-plugins/*` to `./wp-content/mu-plugins/*`
-  all dropins file in `./content-dev/` to  `./wp-content/`

When the base "source" folder is not found, the step is completely skipped.

Note for **Windows** users: if symlinking fails, make sure to run the terminal application **as administrator**.

### `WpCliConfigStep`

This step automatically generates in the project root a [`wp-cli.yml`](https://make.wordpress.org/cli/handbook/config/#config-files) that only contains setting for the WordPress path, allowing WP CLI commands to be ran on the project root, without the need to pass the `--path` argument every time (see WP CLI [documentation](https://make.wordpress.org/cli/handbook/config/#global-parameters)).

The only setting that affects this step is the `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) which will tell where WordPress is located.

### `WpCliCommandsStep`

WP Starter provides a way to run WP CLI commands right after WP Starter finishes its work successfully.

There are several different ways to do this without using WP Starter at all. It could even be possible to add WP CLI commands to [Composer scripts](https://getcomposer.org/doc/articles/scripts.md) so that no more than `composer update` would be necessary to execute both WP Starter and WP CLI commands.

However, by adding commands to WP Starter configuration WP Starter will ensure that WP CLI is available on the system.

First of all, WP Starter will check if WP CLI has been required via Composer. If so, it will do nothing, as it is already available. If WP CLI is not found among installed packages, WP Starter looks for a `wp-cli.phar` in project root, and if even that is not found WP Starter will download the WP CLI phar and will verify it using the hash provided by WP CLI.

It means that adding commands to WP Starter configuration requires exact same effort than adding them to Composer scripts or to any other automation mechanism, but using WP Starter it is possible to get installation of WP CLI "for free".

There's a dedicated documentation chapter, *"Running WP CLI Commands"*, that describes how to setup WP Starter to run WP CLI commands and which WP Starter settings are involved.



## Add, replace or remove steps to run

By default WP Starter will run all the steps, even if some of them are skipped during runtime because conditions to run them are not met, for example the WP CLI commands step will not run if there are no commands to be ran.

WP Starter allows to customize which step has to be ran in different ways:

- by naming steps that should completely be skipped
- by adding new custom steps
- by replacing some default steps with custom ones

### Skipping steps

To skip some steps it is necessary to set the configuration `skip-steps` to an array of **step classes** to be skipped, for example:

```json
{
    "skip-steps": [
        "WeCodeMore\\WpStarter\\Step\\WpCliConfigStep",
        "WeCodeMore\\WpStarter\\Step\\WpCliCommandsStep"
    ]
}
```

### Adding custom steps

It is also possible to add completely custom steps. That can be done via the `custom-steps` setting.

It must be a map of unique step "slugs" to step classes, for example:

```json
{
    "custom-steps": {
        "custom-step-one": "MyCompany\\MyProject\\StepClassNameOne",
        "custom-step-two": "MyCompany\\MyProject\\StepClassNameTwo"
    }
}
```

For how to actually develop the step class please refer to *"Custom Steps Development"* chapter.

To be able to be ran the step classes must be autoloadable, more on this below.

### Replacing default steps

Replace an existing default step is not different from adding a custom step where the step "slug" matches the slug of the default step (see the table at the beginning of this page to read default steps slugs).

For example:

```json
{
    "custom-steps": {
        "build-wp-config": "MyCompany\\MyProject\\WpConfigBuilder"
    }
}
```



## Extending steps via scripts

Before and after each step WP Starter allow users to run "scripts", which are nothing else than PHP callbacks (because of limitation of JSON, only plain PHP functions or class static methods are supported).

The scripts to be ran has to be added to the `scripts` WP Starter setting, which must be a map from scripts slugs to fully-qualified callback name.

Slug must be composed with a prefix that is `pre-` (which means "before") or `post-` (which means "after") followed by the target step slug (custom steps works as well).

```json
{
    "scripts": {
        "pre-build-wp-config": "MyCompany\\MyProject\\Scripts::beforeWpConfigStep",
        "post-custom-step-one": "MyCompany\\MyProject\\runAfterCustomStepOne"
    }
}
```

The function signature for the scripts callback must be:

```php
function (int $result, Step $step, Locator $locator, Composer $composer);
```

Where:

- `$result` is an integer that can be compared with `WeCodeMore\WpStarter\Step\Step` class constants: `Step::ERROR`, `Step::SUCCESS` and `Step::NONE`, that respectively means that the step failed, succeeded or was not executed (e.g. skipped).
  Result is, of course, only available for the _post_ scripts, for _pre_ scripts it will always be `Step::NONE`. Please note that any check on this value should be done by bitmask check and not direct comparison. In fact, it is possible that some "composed" steps, e.g. the "dropins" step, might return an integer equal to `Step::SUCCESS | Step::ERROR` meaning that it *partially* succeeded.
- `$step` is the target step object, that is an instance of `\WeCodeMore\WpStarter\Step\Step`.
- `$locator` is an instance of `WeCodeMore\WpStarter\Util\Locator` an object that provides instances of other objects parts of WP Starter. In the *"Custom Steps Development"* chapter there are more details about this object.
- `$composer` is an instance of `Composer\Composer` the main Composer object.

Besides the scripts for the *actual* steps, there are an additional couple of pre/post scripts: `pre-wpstarter` and `post-wpstarter`, that run respectively before any step starts and after all the steps completed.

For this "special" couple of scripts, the step object passed as second parameter will be an instance of `WeCodeMore\WpStarter\Step\Steps` that is a sort of "steps runner" which implements `Step` interface as well. This is especially interesting for the `pre-wpstarter` script, because callbacks attached to that script can call on the passed `Steps` object its `addStep()` / `removeStep()` methods, adding or removing steps "on the fly".



## Making steps and scripts autoloadable

When creating custom steps or extending steps via scripts it is necessary that step classes and scripts callbacks are autoloadable, or it is not possible to WP Starter to run them.

The obvious way to do that it is to use entries in the via the [`autoload`](https://getcomposer.org/doc/01-basic-usage.md#autoloading) setting in `composer.json`. That obviously works, but considering that Composer is used to require WordPress, and that Composer autoload  is loaded at every WordPress request, "polluting" Composer autoload with things that are not meant to be run in production is probably not a good idea.

WP Starter itself registers a custom autoloader just in time before running its steps, and only register in `composer.json` autoload the minimum required, that is its plugin class and little more.

WP Starter also offers to users the possibility to require a PHP file before starting running steps. This file can then be used to manually require files, declare functions, or register autoloaders.

By default, WP Starter will look for a file  named `"wpstarter-autoload.php"` in project root, but the path can be configured using the **`autoload`** setting.

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
    $locator->io()->writeColorBlock('magenta', "Hello there!\n");
}
```

