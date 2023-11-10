---
title: Settings Cheat Sheet
nav_order: 9
---

# Settings Cheat Sheet

Alphabetically ordered:

|           Key            | Description                                                  |       Default value        |
| :----------------------: | :----------------------------------------------------------- | :------------------------: |
|        `autoload`        | PHP file loaded before WP Starter its steps.<br />Path to the file, relative to root. | "./wpstarter-autoload.php" |
|       `cache-env`        | Whether to cache env for WP requests.                        |            true            |
|    `check-vcs-ignore`    | Whether to check for VCS-ignored paths.<br>By default `true`, can be set to `false` to not do any check.<br>Can also be set to`"ask"`, in which case WP Starter will ask user what to do. |            true            |
|     `command-steps`      | Objects steps of custom steps to add to be ran via WP Starter command.<br />Values must be steps FQCN, keys the step names, matching what `Step::name()` returns.<br />Given classes must be autoloadable. |             {}             |
|    `content-dev-dir`     | Source folder for "development content".<br />Path to the folder, relative to root. |      "./content-dev"       |
|     `content-dev-op`     | Operation to perform for "development content"<br />That is, plugins, themes, MU plugins, translations and dropins shipped with the project.<br />Valid values are "auto", symlink", "copy" and "none".<br />Can also be set to`"ask"`, in which case WP Starter will ask user what to do. |           "auto"           |
| `create-vcs-ignore-file` | Whether to check for VCS-ignore file if it does not exist.<br/>By default `true`, can be set to `false` to not create it.<br/>Can also be set to`"ask"`, in which case WP Starter will ask user what to do. |            true            |
|      `custom-steps`      | Map of custom step names to custom step classes.<br />Classes must be autoloadable. |            null            |
|        `db-check`        | Determine if and how DB check is done.<br />By default `true`, can be set to `false` to not do any check, or to `health` to do a health check using `mysqlcheck` binary. |            true            |
|        `dropins`         | Array of dropins files to move to WP content folder.<br />Can be local paths or remote URLs. |             []             |
|       `dropins-op`       | Operation to perform for "dropins"<br />Valid values are "auto", symlink", "copy" and "none".<br />Can also be set to`"ask"`, in which case WP Starter will ask user what to do. |           "auto"           |
|    `early-hook-file`     | PHP file that adds callbacks to early hooks.<br />Path to the file, relative to root. |            null            |
|   `env-bootstrap-dir`    | Folder where to look for env bootstrap files.<br />Path to the folder, relative to root. |            null            |
|        `env-dir`         | Folder where to look for `.env` file.<br />Path to the folder, relative to root. |            "./"            |
|      `env-example`       | How to deal with `.env.example` file. Can be:<br />- `true` (copy default example file to root)<br />- `false` (do nothing)<br />- path, relative to root, to example file to copy.<br />- `"ask"` (user will be asked what to do) |            true            |
|        `env-file`        | Name of the `.env` file.<br />Will be searched inside `env-dir` |           ".env"           |
|     `install-wp-cli`     | Whether to install WP CLI from phar if necessary.            |            true            |
|      `move-content`      | Whether to move default themes and plugins<br />to project wp-content folder.<br />Can be set to`"ask"`, in which case WP Starter will ask user what to do. |           false            |
|   `prevent-overwrite`    | Array of paths to preserve from overwrite.<br />Paths relative to root, might use glob patterns.<br />Can be set to`"ask"`, in which case WP Starter will ask confirmation before *each* overwrite attempt. |             []             |
| `register-theme-folder`  | Whether to register default themes.<br />Can be set to`"ask"`, in which case WP Starter will ask user what to do. |           false            |
|       `require-wp`       | Whether to check for WP package being required.              |            true            |
|        `scripts`         | Array of script to run before or after steps.<br />An object where key is in the format:<br /> `"pre-{$step}"` or `"post-{$step}"`<br />and value is either a callback.<br />Callbacks must be autoloadable. |             []             |
|       `skip-steps`       | Array of step *names* to skip.                               |             []             |
|     `templates-dir`      | Folder where to look for custom templates.<br />Path relative to root. |            null            |
|       `use-putenv`       | Tell WP Starter to store variables loaded from `.env` files _also_ using `putenv()`.<br />Use only if something is relying on `getenv()` and can not be changed. |           false            |
|    `wp-cli-commands`     | Array of WP CLI commands.<br />Can be a path to a PHP file _returning_ the array of commands, or to a JSON file containing the array.<br />Paths relative to root. |             []             |
|      `wp-cli-files`      | Array of file paths to be passed to WP CLI `eval file`. Can be an array of objects with "file", "args", and "skip-wordpress" properties.<br />Paths relative to root. |             []             |
|       `wp-version`       | When `require-wp` is set to `false` this instruct WP Starter about the WP version that will be used with WP Starter. |            null            |



------

**Next:** [Command-line Interface](10-Command-Line-Interface.md)

---

- [Introduction](01-Introduction.md)
- [Environment Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- ***Settings Cheat Sheet***
- [Command-line Interface](10-Command-Line-Interface.md)
