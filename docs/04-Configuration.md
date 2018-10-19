# WP Starter Configuration

Even if **WP Starter can work without any setup at all**, one of its greatest features is to also be very flexible, because every single aspect of it can be customized according to own needs.



## Where configuration lives

### Configuration in `composer.json`

Just like any other Composer plugin, WP Starter configuration goes into `extra` section of `composer.json`.

More specifically, it goes into a sub-object `wpstarter` in the  `extra` section.

```json
{
    "extra": {
        "wpstarter": {
            "config-name": "config value"
        }
    }
}
```

### Configuration in  `wpstarter.json` 

For better readability and portability it is also possible to have a file named **`wpstarter.json`**, placed besides `composer.json`, that contains only WP Starter configuration (anything that would go in the  `extra.wpstarter` object):

```json
{
    "config-name": "config value"
}
```

WP Starter will recognize the file and will load configuration from there, without the need to have anything set in  `composer.json` or anywhere else.

### Configuration in custom file

Instead of using a file named `wpstarter.json` in root folder it is also possible to tell WP Starter to use a different file to load configuration.

An use case could be to reuse the same WP Starter configuration for many websites that resides under the same parent folder.

To do this, in  `composer.json` it is necessary to use the `extra.wpstarter` configuration to set the path of the custom file. The path must be relative to the folder containing the `composer.json`:

```json
{
    "extra": {
        "wpstarter": "../wpstarter-shared-config.json"
    }
}
```

This also enables to have the configuration file available in a custom Composer package to require via Composer and make it available to WP Starter by pointing the file in vendor folder:

```json
{
    "extra": {
        "wpstarter": "./vendor/my-company/wp-starter-shared/config.json"
    }
}
```

### Configuration precedence

In case *both* `wpstarter` section in  `composer.json` (no matter if as object or as path to a custom file) and  `wpstarter.json` file are there, both configuration are loaded and in the case the same configuration is placed in both places using different values, the value in  `wpstarter.json` file will take precedence.



## Generic configuration

There are two configuration values that affect WP starter that are not placed in any of the "places" listed above.

Those are:

- `"wordpress-install-dir"`
- `"wordpress-content-dir"`

These two configuration values might contain a custom path where to place, respectively, WordPress core files and WordPress `wp-content` files.

`"wordpress-install-dir"` is not even a WP Starter configuration, but it comes from WordPress core installer. However, even if that installer is not used, this configuration should be used to tell WP Starter where WordPress core files are located.

This configuration defaults to `"./wordpress"` meaning a `wordpress` folder inside root.

`"wordpress-content-dir"` has been introduced by WP Starter and it is located differently from other WP starter config to have a symmetry with `"wordpress-install-dir"`.

This configuration defaults to `"./wp-content"` meaning a `wp-content` folder inside root.



## All available WP Starter settings

No matter if set via `composer.json`, `wpstarter.json` or custom config file, at the end the WP Starter will be a map of configuration keys to configuration values.

The following table describes all the available configuration key and the related accepted values and defaults.

|          Key          | Description                                                  |  Default value   |
| :-------------------: | :----------------------------------------------------------- | :-------------: |
|    content-dev-op     | Operation to perform for "development content"<br />i.e. plugins and themes shipped with the project.<br />Valid values are "symlink", "copy" and "none".<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |    "symlink"    |
|    content-dev-dir    | Source folder for "development content".<br />Relative to root. | "./content-dev" |
|     custom-steps      | Array of custom steps to add to WP Starter.<br />Fully qualified class names of the steps.<br />Given classes must be autoloadable. |       []        |
|        dropins        | Array of dropins files to move to WP content folder.<br />Can be local path or remote URLs. |       []        |
|    early-hook-file    | PHP file where to add callbacks to very early hooks.<br />Must be path to file, relative to root.<br /> |      null       |
|   env-bootstrap-dir   | Folder where to look for env-specific bootstrap files.<br />Path to folder relative to root. |      null       |
|        env-dir        | Folder where to look for `.env` file.<br />Path to folder relative to root. |      "./"       |
|      env-example      | How to deal with `.env.example` file. Can be:<br />- `true` (copy default example file to root)<br />- `false` (do nothing)<br />- path, relative to root, to example file to copy.<br />- `"ask"` (user will be asked what to do) |      true       |
|       env-file        | Name of the `.env` file.<br />Will be searched inside `env-dir` |     ".env"      |
|    install-wp-cli     | Whether to install WP CLI from phar if necessary.            |      true       |
|     move-content      | Whether to move default themes and plugins<br />to project wp-content folder.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |      false      |
|   prevent-overwrite   | Array of paths that WP Starter has to not overwrite.<br />Path relative to root, might use glob patterns.<br />Can be set to`"ask"`, in which case<br /> WP Starter will ask confirmation before each overwrite. |       []        |
| register-theme-folder | Whether to register default themes for the project.<br />When `true`, will force `move-content` to `false`.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |      false      |
|      require-wp       | Whether to check for WP package being required.              |      true       |
|        scripts        | Array of script to run either before or after steps.<br />An object where key is in the format:<br /> `"pre-{$stepname}"` or `"post-{$stepname}"`<br />and value is either a function or a static method.<br />Functions (or methods) must be autoloadable. |       []        |
|      skip-steps       | Array of step names to skip.                                 |       []        |
|     templates-dir     | Folder where to look for custom file templates.<br />Relative to root. |      null       |
|    unknown-dropins    | How to deal with non-standard dropins.<br />Can be:<br />- `true` just install them<br />- `false` just skip them<br />- `"ask"` will ask the user what to do |      false      |

More detailed explanation of each setting is available in the *"WP Starter Steps"* chapter.