---
title: Command-line Interface
nav_order: 10
---

# Command-line Interface
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## The `wpstarter` Command

By default, **WP Starter runs every time `composer update` or `composer install` is run**, including the very first time a project dependencies are installed.

Sometimes might be desirable to *only* perform WP Starter steps (according to configuration) without also updating Composer dependencies.

This can be obtained via the `wpstarter` Composer command.

```shell
composer wpstarter
```



## Command Options and Arguments

What shown above is just the simplest form of the command that tells WP Starter to run all the steps, including those in `custom-scripts` setting, but skipping those in `skip-steps` setting.

By running `composer help wpstarter` it is possible to obtain more info about the command option and arguments. The output (stripped from "generic" Composer options) is something like:

```
Description:
  Run WP Starter workflow.

Usage:
  wpstarter [options] [--] [<steps>]...

Arguments:
  steps                     Which steps to run (or to skip).
                            Separate step names with a space.
Options:
      --skip                Enable opt-out mode: provided steps names
                            are those to skip, not those to run.
      --skip-custom         Skip any step defined in "custom-steps" setting.
      --ignore-skip-config  Ignore "skip-steps" config.
      --list-steps          List available steps.
```



### Customizing steps to run ("opt-in" mode)

The first thing to notice is that it is possible to pass an array of step to run, by listing them after the command, for example:

```shell
composer wpstarter publishcontentdev wpcli
```

In the example above only *"publishcontentdev"* and *"wpcli"* steps would be executed.

### Skip ("opt-out" mode)

By using the `--skip` option it is possible to run the command in "opt-out" mode: the list of step names provided to command, are those to be skipped, and not those to be run.

This is useful when the steps to skip are less than the steps to run. 

Note that when using this option, one or more step names are required. By running the command with only `--skip` but no step names, will make the command fail.

```shell
composer wpstarter --skip publishcontentdev wpcli
```

In the example above, WP starter would run all the default steps, plus all custom steps, but skipping both *"publishcontentdev"*, *"wpcli"* and also skipping any step listed in the `skip-steps` config.

### Skip custom

Sometimes might be desirable to only run the default steps, skipping those that are listed in `custom-steps` setting. 

This could surely be obtained by using the option `--skip` and then listing all the step names present in the config, but can be done in a simpler way via the `--skip-custom` flag.

For example:

```shell
composer wpstarter --skip-custom
```

The command above would have the same effect of:

```shell
composer wpstarter --skip step-one step-two
```

assuming that "*step-one*" and "*step-two*" are all the steps listed in the `custom-steps` setting. 

Note that this flag is ignored when running in the "opt-in" mode (i.e. listing one or more steps without using the `--skip` flag).
Reason is opt-in mode takes precedence over opt-out mode, so if the command is run like:

```shell
composer wpstarter step-one --skip-custom
```

"step-one" will be run, even if it is a custom step listed in the  `custom-steps` setting. 

### Ignore skip config

The flag `--ignore-skip-config` tells WP Starter to don't take into account the `skip-steps` configuration.

It can be used alone:

```shell
composer wpstarter --ignore-skip-config
```

Which means run all the default steps, plus the custom steps, no matter what is in the `skip-steps` configuration.

Or can be used in combination with `--skip` and `--skip-custom`. For example:

```shell
composer wpstarter --ignore-skip-config --skip wpcli
```

means run all the default steps, plus the custom steps, but don't run *"wpcli"* step. Or even:

```shell
composer wpstarter --ignore-skip-config --skip-custom --skip wpcli
```

which basically means run only all the default steps except just *"wpcli"*.

Note that this option does nothing when in *"opt-in"* mode: when a series of step to run are provided those are always run, so the  `skip-steps` config is already ignored.

### Note on simplest command form

The simples form of the command:

```shell
composer wpstarter
```

has to be considered as "opt-out" form where no step are selected to be skipped, rather than "opt-in" form where no step has been selected to run. This is why ` --ignore-skip-config` and `--skip-custom` can be used as the only option even if it has been said those are ignored in "opt-in" mode.



## Command-only steps

The step names passed as arguments to `wpstarter` command in "opt-in" mode must be recognized as valid steps to be run.

Which means that they are either default steps or steps added to `custom-steps` configuration.

In both cases those steps would also be run every time Composer installs or updates dependencies or when the `wpstarter` command is run on its simplest form.

Sometimes, however, it might be desirable run some steps on demand ("opt-in" mode) but **not** to run them after every Composer install or update.

That can be obtained by adding steps to `command-steps` setting, which format is identical to `custom-steps`.

That setting has the exact same format of `custom-steps` setting, but steps added to `command-steps` **are only taken into account when running the command in "opt-in" mode**, allowing for "picking" them explicitly.

For example, imagine a custom step that runs `yarn` for each package of type "wordpress-plugin" or "wordpress-theme" of a given specific vendor. Such script could be written without making a WP Starter step, but using WP Starter is convenient because it provides an:

- easy way access to relevant paths
- easy way to find target packages, thanks to WP Starter `PackageFinder` object
- easy (and OS-agnostic) way to run shell processes via WP Starter `SystemProcess` object built on top of [Symfony `Process` component](https://symfony.com/doc/current/components/process.html).

What's relevant here about such step class would be:

```php
namespace MyCompany\MyProject;

class YarnStep implements \WeCodeMore\WpStarter\Step\Step {
    // ...
    public function name(): string {
        return 'yarn';
    }
    // ...
}
```

Adding this step to `command-steps` setting like this:

```json
{
    "command-steps": {
        "yarn": "MyCompany\\MyProject\\YarnStep"
    }
}
```

It would be possible to run:

```shell
composer wpstarter yarn
```

and run the step on demand, ignoring the step when WP Starter runs after Composer install/update or even when the command is run in "opt-out" mode.

Note that steps in  `command-steps` can be combined in "opt-in" mode with other steps, either default or custom. For example:

```shell
composer wpstarter yarn wpcli step-one
```

will run the three steps with no issue.


## Listing commands

```shell
composer wpstarter --list-steps
```

Does execute nothing, but lists all available steps, including custom, but excluding those disabled in config or explicitly passed using the `--skip` flag.

Can be used in combination with other flags like `--skip`, `--skip-custom`, and `--ignore-skip-config`.

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- ***Command-line Interface***

