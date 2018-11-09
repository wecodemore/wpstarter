# WP Starter Configuration

Even if **WP Starter can work without any setup at all**, one of its greatest features is to also be very flexible, because every single aspect of it can be customized according to own needs.



## Configuration in `composer.json`

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



## Configuration in  `wpstarter.json` 

For better readability and portability it is also possible to have a file named **`wpstarter.json`**, placed besides `composer.json`, that contains only WP Starter configuration (anything that would go in the  `extra.wpstarter` object):

```json
{
    "config-name": "config value"
}
```

WP Starter will recognize the file and will load configuration from there, without the need to have anything set in  `composer.json` or anywhere else.



## Configuration in custom file

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

This also enables to have the configuration file available in a custom Composer package and make it available to WP Starter by pointing the file in vendor folder:

```json
{
    "extra": {
        "wpstarter": "./vendor/my-company/wp-starter-shared/config.json"
    }
}
```



## Configuration precedence

In case *both* `wpstarter` section in  `composer.json` (no matter if as object or as path to a custom file) and  `wpstarter.json` file are there, both configurations are loaded and in the case the same configuration values are placed in both places, the value in  `wpstarter.json` will take precedence.



## Generic configuration

There are two configuration values that affect WP Starter that are **not** placed inside the `extra.wpstarter` object, nor can be set in `wpstarter.json`:

- `"wordpress-install-dir"`
- `"wordpress-content-dir"`

These two configuration values might contain a custom path where to place, respectively, WordPress core files and WordPress "content packages": plugin, themes, MU plugins, and dropins.

`"wordpress-install-dir"` is not even a WP Starter specific configuration, but it comes from WordPress core installer. However, even if that installer is not used, this configuration should be used to tell WP Starter where WordPress core files are located.

This configuration defaults to `"./wordpress"` meaning a `wordpress` folder inside project root.

`"wordpress-content-dir"` has been introduced by WP Starter and it is located differently from other WP Starter settings to be in symmetry with `"wordpress-install-dir"`.

This configuration defaults to `"./wp-content"` meaning a `wp-content` folder inside project root.




------

**Next:** [WP Starter Steps](05-WP-Starter-Steps.md)

---

- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- ***> WP Starter Configuration***
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [Running WP CLI Commands](07-Running-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [WP Starter Command](10-WP-Starter-Command.md)