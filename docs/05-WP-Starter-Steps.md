# WP Starter Steps

At its core, WP Starter is a command line script that performs, in order, a set of tasks that are called **"steps"**.

A step is nothing more than a PHP class performing a given specific operation.



## Default steps

The (current) list of default WP Starter steps is (in order of execution):

| Slug                | Class Name<sup>1</sup> | Notes                 |
| ------------------- | ---------------------- | --------------------- |
| check-paths         | `CheckPathStep`        | blocking              |
| build-wp-config     | `WpConfigStep`         | create file, blocking |
| build-index         | `IndexStep`            | create file, blocking |
| flush-env-cache     | `FlushEnvCacheStep`    |                       |
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
- "*create file*" indicates a step will create a file from a template.
- "*optional*" indicates a step that (might) ask the user a confirmation before running



## Customizable templates

Several steps produce files. Files are built using "templates" as a base, where placeholders in the format `{{{PLACEHOLDER}}}` are replaced with values calculated by the step.

Templates are located in the `/templates` directory under WP Starter root.

However, users might replace all or some templates via the `templates-dir` configuration.

If that setting is filled and if files with the same name as the ones in the default templates directory are found in the given directory, those will be used instead of the standard ones.

Considering that `templates-dir` could be any folder, and considering that WP Starter always run _after_ Composer install/update, it is possible to have Composer packages containing WP Starter templates and so having reusable templates to use in different projects.



## Steps details

### `CheckPathStep`

This task will check that the WP Starter is able to reach required paths. It will check that WordPress and WP content folder exists and that the Composer autoload file is found.

This ensures that subsequent steps will perform operations in the right paths. Moreover, it ensures that at the moment WP Starter is performing its steps Composer will have finished installing all dependencies.

WP Starter will not proceed with other steps if this fails.

### `WpConfigStep`

This is the main WP Starter step. It creates a `wp-config.php` that setups WordPress based on environment variables and adds WP Starter specific features as described in the **WordPress Integration**  chapter.

Just like any other step that builds a file, by overriding the template it is possible to have a completely different outcome, so what is being described in this documentation is the behavior of the file generated with a _default_ template.

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

The default `wp-config.php` template used by WP Starter supports "sections", that allow implementors to append/pre-pend/replace only parts of the generated `wp-config.php` without changing the other parts and without using a custom template. The [WordPress Integration](03-WordPress-Integration.md) section has documentation for this feature.

### `IndexStep`

In a typical WP Starter powered installation, WordPress is not installed at the webroot, meaning that WordPress `ABSPATH` (the directory that contains `/wp-includes` and `/wp-admin`) is not the webroot.

This is a fairly common setup even for installations not using WP Starter. There's a codex page that explain the approaches to [give WordPress its own directory.](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory)

As described there, we need to create an `index.php` file located at webroot. This step does exactly that.

The only setting affecting this step is the `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) which tells Composer where to place WordPress files and folders.

WP Starter will not proceed with other steps if this fails for any reason.

### `FlushEnvCacheStep`

This step will clear the environment cache file if found. See *"WordPress Integration"* chapter for more info about cached environment.

There are no configurations affecting this step.

### `MuLoaderStep`

MU plugins (aka "Must-use plugins") are special single-file plugins that WordPress always executes, in fact, they can't be activated and deactivated like regular plugins.

MU plugins are supported by [Composer Installers](http://composer.github.io/installers/) and so Composer packages containing MU plugins will be correctly installed in the `/mu-plugins` subfolder inside WP content folder.

However, Composer will place each of them in an **own subdirectory**, but unfortunately WordPress is not able to load MU plugins from subfolders: for WordPress a MU plugin is a single file placed *directly* inside `wp-content/mu-plugins`.

This step creates a MU plugin, placed in `wp-content/mu-plugins` folder, that loads all the MU plugins that Composer placed in its own subfolder.

There's no configuration affecting this step. The MU plugins to load are identified by WP Starter looking at installed Composer packages with [type](https://getcomposer.org/doc/04-schema.md#type) `"wordpress-muplugin"`.

If no MU plugin packages are installed via Composer, the step is entirely skipped.

### `EnvExampleStep`

It is a quite standard practice for applications that support `.env` files to provide a `.env.example` file as a blueprint of the available configurations.

WP Starter ships with a template for such file that includes all the env var names that resemble WP configuration constants and this step copies that file into the project root folder.

The step outcome might actually change based on the `env-example` setting. By setting it to `false`, the step is entirely skipped. Moreover, the step is also skipped if an `.env` file is found, as it makes no sense providing an example for something that exists already.

When `env-example` setting is `true` WP Starter will copy the template in project root, and when the setting is `"ask"` WP Starter will ask the user before copying.

Finally, `env-example` setting can also be a path to copy the example file from, or even a URL from where to download it. This latter is not recommended, because no security check is done on the downloaded file, so make sure at least to point to a trusted server and use HTTPS instead of plain HTTP.

## `DropinsStep`

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

WP Starter, via this step, allows to do that.

The main configuration involved is `dropins` which is an array of files to copy.

Besides local paths (which includes files pulled as part of Composer packages in vendor dir) the step is also capable to use arbitrary URLs as source. This latter is not recommended, because no security check is done on the downloaded file, so make sure at least to point to a trusted server and use HTTPS instead of plain HTTP.

An additional configuration that affects the step is `unknown-dropins`. By setting this to `true` WP Starter will ignore the list of supported dropins from WordPress and just copy every file provided in the `dropins` setting to WP content folder. The default is `false`, because setting this to true, in combination of usage of URLs as source might be a security issue. The setting could also have the value of "ask" and in that case WP Starter will ask confirmation to the user before copying a file not recognized as a dropin.

### `MoveContentStep`

WP Starter assumes that WordPress is installed via Composer, and popular WordPress packages include default themes and plugins (Twenty* themes, "Hello Dolly" and "Akismet" plugins).

Because WP Starter normally uses a non-standard WP content folder located outside of WordPress folder, those default themes and plugins are not recognized by WordPress.

The scope of this step is to move the default plugins and themes from the `/wp-content` folder to the project content folder, so that WordPress will recognize them.

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

There are two settings that affect how the step works: `content-dev-op` and `content-dev-dir`.

`content-dev-op`  can be one of *"symlink"* (default), *"copy"* or *"none"* and it tells WP Starter what to do with the "development content" (that is plugin and themes developed in the project repository).

`content-dev-dir` tells WP Starter where to look for development content folders. By default it is the `/content-dev` folder under the project root.

So by default, this step will symlink:

-  `./content-dev/plugins/*` to `./wp-content/plugins/*`
-  `./content-dev/themes/*` to `./wp-content/themes/*`
-  `./content-dev/mu-plugins/*` to `./wp-content/mu-plugins/*`
-  all dropins file in `./content-dev/` to  `./wp-content/`

When the base "source" folder is not found, the step is completely skipped.

Note for **Windows** users: if symlinking fails, make sure to run the terminal application **as administrator**.

### `WpCliConfigStep`

This step automatically generates in the project root a [`wp-cli.yml`](https://make.wordpress.org/cli/handbook/config/#config-files) that only contains setting for the WordPress path, allowing WP CLI commands to be run on the project root, without the need to pass the `--path` argument every time (see WP CLI [documentation](https://make.wordpress.org/cli/handbook/config/#global-parameters)).

The only setting that affects this step is the `wordpress-install-dir` specific of [WordPress core installer](https://github.com/johnpbloch/wordpress-core-installer) which will tell where WordPress is located.

### `WpCliCommandsStep`

WP Starter provides a way to run WP CLI commands right after WP Starter finishes its work successfully.

There are several different ways to do this without using WP Starter at all. It could even be possible to add WP CLI commands to [Composer scripts](https://getcomposer.org/doc/articles/scripts.md) so that no more than `composer update` would be necessary to execute both WP Starter and WP CLI commands.

However, by adding commands to WP Starter configuration WP Starter will ensure that WP CLI is available on the system.

First of all, WP Starter will check if WP CLI has been required via Composer. If so, it will do nothing, as it is already available. If WP CLI is not found among installed packages, WP Starter looks for a `wp-cli.phar` in project root, and if even that is not found WP Starter will download the WP CLI phar and will verify it using the hash provided by WP CLI.

It means that adding commands to WP Starter configuration requires the same effort as adding them to Composer scripts or to any other automation mechanism, but by using WP Starter it is possible to get installation of WP CLI "for free".

There's a dedicated documentation chapter, **Running WP ClI Commands**, that describes how to setup WP Starter to run WP CLI commands and which WP Starter settings are involved.



## Add, replace or remove steps to run

By default WP Starter will run all the steps, even if some of them are skipped during runtime because conditions to run them are not met, for example the WP CLI commands step will not run if there are no commands to be run.

WP Starter allows to customize which step has to be run in different ways:

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

To be able to be run, the step classes must be autoloadable. More on this in the  **Custom Steps Development** chapter.

### Replacing default steps

Replace an existing default step is not different from adding a custom step where the step "slug" matches the slug of the default step (see the table at the beginning of this page to read default step slugs).

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

The scripts to be run has to be added to the `scripts` WP Starter setting, which must be a map from scripts slugs to an array of fully-qualified callback names.

Slug must be composed with a prefix that is `pre-` (which means "before") or `post-` (which means "after") followed by the target step slug (custom steps works as well).

```json
{
    "scripts": {
        "pre-build-wp-config": [
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
    

  $result is, of course, only available for the _post_ scripts. For _pre_ scripts it will always be `Step::NONE`. Please note that any check on this value should be done by a bitmask check and not direct comparison. In fact, it is possible that some "composed" steps, e. g. the "dropins" step, might return an integer equal to `Step::SUCCESS | Step::ERROR` meaning that it *partially* succeeded.
- `$step` is the target step object, that is an instance of `\WeCodeMore\WpStarter\Step\Step`.
- `$locator` is an instance of `WeCodeMore\WpStarter\Util\Locator` an object that provides instances of other objects parts of WP Starter. In the **Custom Steps Development** chapter there are more details about this object.
- `$composer` is an instance of `Composer\Composer` the main Composer object.

Besides the scripts for the *actual* steps, there are an additional couple of pre/post scripts: `pre-wpstarter` and `post-wpstarter`, that run respectively before any step starts and after all the steps are completed.

For this "special" couple of scripts, the step object passed as a second parameter will be an instance of `WeCodeMore\WpStarter\Step\Steps` that is a sort of "steps runner" which implements `Step` interface as well. This is especially interesting for the `pre-wpstarter` script, because callbacks attached to that script can call on the passed `Steps` object via its `addStep()` / `removeStep()` methods, adding or removing steps "on the fly".



------

**Next:** [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)

---

- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- ***> WP Starter Steps***
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [Running WP CLI Commands](07-Running-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [WP Starter Command](10-WP-Starter-Command.md)

