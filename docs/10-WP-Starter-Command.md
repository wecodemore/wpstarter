# WP Starter Command

By default WP Starter runs every time `composer update` or `composer install` is ran, including the very first time a project dependencies are installed.

Sometimes might be desirable to *only* perform WP Starter steps (according to configuration) without also updating Composer dependencies.

This can be obtained via the `wpstarter` Composer command.

```shell
composer wpstarter
```

Nice thing about the command is that is accepts an array of steps names to be run. For example:

```shell
composer wpstarter publish-content-dev wp-cli
```

In the example above only "publish-content-dev" and "wp-cli-commands" steps would be ran.



## Command-only steps

The commands names passed as argument to `wpstarter` command must be recognized as valid steps to be run. Which means that they are either names of default steps or names of steps added to `custom-steps` configuration.

In both cases those steps would also be run every time Composer installs or updated dependencies. Sometimes, however, might be desirable to make some steps available to `wpstarter` command without making them automatically run on Composer install or update.

That can be done by adding steps to `command-steps` setting, which format is identical to `custom-steps`.

### An example

Imagine to desire a custom step that run `yarn` for each package of type "wordpress-plugin" or "wordpress-theme" of a given specific vendor.

This script could be written without making a WP Starter step, but using WP Starter is more convenient because:

- easy way access to relevant paths
- easy way to find target packages, thanks to WP Starter `PackageFinder` object (provided by `Locator::packageFinder()`)
- easy (and OS-agnostic) way to run shell process via WP Starter `SystemProcess` object (provided by `Locator::systemProcess()`) build on top of [Symfony `Process` component](https://symfony.com/doc/current/components/process.html).

The *relevant* part of such step could be:

```php
class YarnStep implements Step {
    
    public function __construct(Locator $locator)
    {
        $this->finder = $locator->packageFinder();
        $this->process = $locator->systemProcess();
        $this->io = $locator->io();
        $this->paths = $locator->paths();
    }
    
    public function name(): string
    {
        return 'yarn';
    }
    
    public function run(): int
    {
        // Find all packages named "mycompany/*"
        $packages = $this->finder->findByVendor('mycompany');
        if (!$packages) {
            return Step::NONE;
        }
        
        $found = 0;
        $error = 0;
        
        foreach ($packages as $package) {
            // Skip if this is not a WordPress (mu-)plugin or theme
            if (strpos($package->getType(), 'wordpress-') !== 0) {
                continue;
            }
            
            $found++;
            
            $name = '<comment>' . $package->getName() . '</comment>';
            $this->io->writeIfVerbose("  - Running 'yarn' for {$name}...");
            
            // Find the absolute path to package folder...
            $cwd = $this->paths->root($package->findPathOf($package));
            // ...and run yarn using that folder as working dir.
            if ($this->process->execute('yarn', $cwd)) {
                $this->io->writeIfVerbose('    <fg=green>Done</>');
                continue;
            }
            
            $error++;
            $this->io->writeErrorLineIfVerbose('    <fg=red>Error</>');
        }
        
        if (!$error || !$found) {
            // Either nothing was done, or everything ran successfully.
            return $runned ? Step::SUCCESS : Step::NONE;
        }
        
        return $error === $found
            ? Step::ERROR                   // everything failed
            : Step::ERROR | Step::SUCCESS;  // something failed
    }
}
```

Adding this step to  `command-steps` setting:

```json
{
    "command-steps": "yarn"
}
```

It would be possible to run `composer wpstarter yarn` and make the step run for us on demand, and not every time Composer installs or updates dependencies.