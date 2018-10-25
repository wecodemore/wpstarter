# WP Starter Command

By default WP Starter runs every time `composer update` or `composer install` is ran, including the very first time a project dependencies are installed.

Sometimes might be desirable to *only* perform WP Starter steps (according to configuration) without also updating Composer dependencies.

This can be obtained via the `wpstarter` Composer command.

```shell
composer wpstarter
```

Nice thing about the command is that is accepts an array of steps names to be run. For example:

```shell
composer wpstarter publish-content-dev wp-cli-commands
```

In the example above only "publish-content-dev" and "wp-cli-commands" steps would be ran.



## Command-only steps

The commands names passed as argument to `wpstarter` command must be recognized as valid steps to be run. Which means that they are either names of default steps or names of steps added to `custom-steps` configuration.

In both cases those steps would also be run every time Composer installs or updated dependencies. Sometimes, however, might be desirable to make some steps available to `wpstarter` command without making them automatically run on Composer install or update.

That can be done by adding steps to `command-steps` setting, which format is identical to `custom-steps`.

### An example

Imagine to desire a custom step that run `npm install` for each package of type "wordpress-plugin" or "wordpress-theme" of a given specific vendor.

This script could be written without making a WP Starter step, but using WP Starter is more convenient because:

- easy way access to relevant paths
- easy way to find target packages, thanks to WP Starter `PackageFinder` object (provided by `Locator::packageFinder()`)
- easy (and OS-agnostic) way to run shell process via WP Starter `SystemProcess` object (provided by `Locator::systemProcess()`) build on top of [Symfony `Process` component](https://symfony.com/doc/current/components/process.html).

The relevant part of such step could be just:

```php
class NpmStep implements Step {
    
    public function __construct(Locator $locator)
    {
        $this->finder = $locator->packageFinder();
        $this->process = $locator->systemProcess();
        $this->io = $locator->io();
    }
    
    public function name() {
        return 'npm';
    }
    
    public function run() {
        foreach ($this->finder->findByVendor('mycompany') as $package) {
            if (strpos($package->getType(), 'wordpress-') === 0) {
                $cwd = $package->findPathOf($package);
                $this->process->execute('npm install', $cwd);
            }
        }
        $this->io->writeSuccess("NPM step done.");
	}
}
```

Adding this step to  `command-steps` setting:

```json
{
    "command-steps": "npm"
}
```

It would be possible to run `composer wpstarter npm` and make the step run for us, without having it run every time Composer install or update dependencies.