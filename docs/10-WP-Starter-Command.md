# WP Starter Command

By default WP Starter runs every time `composer update` or `composer install` is ran, including the very first time a project dependencies are installed.

Sometimes might be desirable to *only* perform WP Starter steps (according to configuration) without also updating Composer dependencies.

This can be obtained via the `wpstarter` Composer command.

```shell
composer wpstarter
```



## Customizing steps to run

Nice thing about the `wpstarter` command is that it accepts an array of steps names to be run. For example:

```shell
composer wpstarter publish-content-dev wp-cli
```

In the example above only "publish-content-dev" and "wp-cli" steps would be ran.

When the steps to skip are just one or a few, it is convenient to list the steps to skip instead of those to be ran. This can be done by passing the `--skip` flag. For example:

```shell
composer wpstarter --skip wp-cli
```

In the example above all the default steps plus those defined in the `custom-steps` setting will be run, skipping only the "wp-cli" step.



## Command-only steps

The step names passed as argument to `wpstarter` command must be recognized as valid steps to be run. Which means that they are either names of default steps or names of steps added to `custom-steps` configuration.

In both cases those steps would also be run every time Composer installs or updated dependencies. Sometimes, however, might be desirable to make some steps available to  be ran via `wpstarter` command without run them automatically on Composer install or update.

That can be obtained by adding steps to `command-steps` setting, which format is identical to `custom-steps`.

### An example

Imagine to desire a custom step that run `yarn` for each package of type "wordpress-plugin" or "wordpress-theme" of a given specific vendor.

This script could be written without making a WP Starter step, but using WP Starter is convenient because:

- easy way access to relevant paths
- easy way to find target packages, thanks to WP Starter `PackageFinder` object (provided by `Locator::packageFinder()`)
- easy (and OS-agnostic) way to run shell process via WP Starter `SystemProcess` object (provided by `Locator::systemProcess()`) build on top of [Symfony `Process` component](https://symfony.com/doc/current/components/process.html).

What's relevant here about such step class would be:

```php
namespace MyCompany\MyProject;

class YarnStep implements \WeCodeMore\WpStarter\Step\Step {
    
    public function name(): string
    {
        return 'yarn';
    }
    
    // ...
}
```

Adding this step to  `command-steps` setting like this:

```json
{
    "command-steps": {
        "yarn": "MyCompany\\MyProject\\YarnStep"
    }
}
```

It would be possible to run `composer wpstarter yarn` and run the step only on demand, and not every time Composer installs or updates dependencies.

Note that in "skip mode" command-only steps are ignored.