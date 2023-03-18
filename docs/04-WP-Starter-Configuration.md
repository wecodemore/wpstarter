---
title: Configuration
nav_order: 4
---

# Configuration
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## All configuration is optional

**WP Starter can work without any setup at all**, however one of its greatest features is its flexibility that allows for fine-grained customization of every aspect according to the project requirements.



## Configuration in `composer.json`

Like other Composer plugins, WP Starter configuration goes into `extra` section of `composer.json`.

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

For better readability and portability it is also possible to have, in the same folder as `composer.json`, a file named **`wpstarter.json`** containing only WP Starter configuration (anything that would go in the  `extra.wpstarter` object):

```json
{
    "config-name": "config value"
}
```

If that file is present, WP Starter will recognize it and will load configuration from there.



## Configuration in a custom file

Instead of using a file named `wpstarter.json` in root folder it is also possible to tell WP Starter to use a different file to load configuration.

A use case could be to reuse the same WP Starter configuration for many websites that resides under the same parent folder.

To do this, in  `composer.json` it is necessary to use the `extra.wpstarter` configuration to set the path of the custom file. The path must be relative to the folder containing the `composer.json`:

```json
{
    "extra": {
        "wpstarter": "../wpstarter-shared-config.json"
    }
}
```

This also enables to have the configuration file available in a separate Composer package and make it available to WP Starter by pointing to the file in vendor folder:

```json
{
    "extra": {
        "wpstarter": "./vendor/my-company/wp-starter-shared/config.json"
    }
}
```



## Configuration precedence

When having both `wpstarter.json` file and `extra.wpstarter` section in `composer.json`, WP Starter will load configuration from both, but in case the same setting is set in both places, the  `wpstarter.json` file takes precedence, also in the case `extra.wpstarter` is not a literal object but a path to a separate configuration file.



## Generic configuration

There are two configuration values that affect WP Starter that **cannot** be placed inside the `extra.wpstarter` object, nor can they be set in `wpstarter.json`:

- `"wordpress-install-dir"`
- `"wordpress-content-dir"`

These two configuration values might contain a custom path where to place, respectively, WordPress core files and WordPress "content packages": plugin, themes, MU plugins, and dropins.

`"wordpress-install-dir"` is not even a WP Starter specific configuration, but was first introduced by the [John P. Block's WordPress core installer](https://packagist.org/packages/johnpbloch/wordpress-core-installer) and then also supported by [Roots' one](https://packagist.org/packages/roots/wordpress-core-installer).

Regardless of whether any of the above WordPress core installers is used, **`wordpress-install-dir` configuration is required** to tell WP Starter where WordPress core files are located. It defaults to `"./wordpress"` meaning a `wordpress` folder inside project root.

`"wordpress-content-dir"` has been introduced by WP Starter and it is located differently from other WP Starter settings to be in symmetry with `"wordpress-install-dir"`. It defaults to `"./wp-content"` meaning a `wp-content` folder inside project root.




------

**Next:** [WP Starter Steps](05-WP-Starter-Steps.md)

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- ***WP Starter Configuration***
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [Command-line Interface](10-Command-Line-Interface.md)
