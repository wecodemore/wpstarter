## Settings Cheat Sheet

Alphabetically ordered:

|          Key          | Description                                                  |       Default value        |
| :-------------------: | :----------------------------------------------------------- | :------------------------: |
|       autoload        | PHP file loaded before WP Starter its steps.<br />Path relative to root. | "./wpstarter-autoload.php" |
|       cache-env       | Whether to cache env for WP requests.                        |            true            |
|    content-dev-op     | Operation to perform for "development content"<br />i.e. plugins and themes shipped with the project.<br />Valid values are "symlink", "copy" and "none".<br />Can also be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |         "symlink"          |
|    content-dev-dir    | Source folder for "development content".<br />Relative to root. |      "./content-dev"       |
|     command-steps     | Custom steps to be ran via WP Starter command.<br />Array of fully-qualified step class names.<br />Given classes must be autoloadable. |             []             |
|     custom-steps      | Array of custom steps to add to WP Starter.<br />Array of fully-qualified step class names.<br />Given classes must be autoloadable. |             []             |
|        dropins        | Array of dropins files to move to WP content dir.<br />Can be local paths or remote URLs. |             []             |
|    early-hook-file    | PHP file that adds callbacks to early hooks.<br />Must be path to file, relative to root.<br /> |            null            |
|   env-bootstrap-dir   | Folder where to look for env bootstrap files.<br />Path to folder relative to root. |            null            |
|        env-dir        | Folder where to look for `.env` file.<br />Path to folder relative to root. |            "./"            |
|      env-example      | How to deal with `.env.example` file. Can be:<br />- `true` (copy default example file to root)<br />- `false` (do nothing)<br />- path, relative to root, to example file to copy.<br />- `"ask"` (user will be asked what to do) |            true            |
|       env-file        | Name of the `.env` file.<br />Will be searched inside `env-dir` |           ".env"           |
|    install-wp-cli     | Whether to install WP CLI from phar if necessary.            |            true            |
|     move-content      | Whether to move default themes and plugins<br />to project wp-content folder.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |           false            |
|   prevent-overwrite   | Array of paths to preserve from overwrite.<br />Paths relative to root, might use glob patterns.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask confirmation<br />before *each* overwrite attempt. |             []             |
| register-theme-folder | Whether to register default themes.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |           false            |
|      require-wp       | Whether to check for WP package being required.              |            true            |
|        scripts        | Array of script to run before or after steps.<br />An object where key is in the format:<br /> `"pre-{$step}"` or `"post-{$step}"`<br />and value is either a callback.<br />Callbacks must be autoloadable. |             []             |
|     skip-db-check     | When true no DB check is done<br />and no related env vars are set. |           false            |
|      skip-steps       | Array of step *names* to skip.                               |             []             |
|     templates-dir     | Folder where to look for custom templates.<br />Path relative to root. |            null            |
|    unknown-dropins    | How to deal with non-standard dropins.<br />Can be:<br />- `true` just install them<br />- `false` just skip them<br />- `"ask"` will ask the user what to do |           false            |



---

|                                                            |                                             |
| ---------------------------------------------------------- | ------------------------------------------: |
| [Custom Steps Development](08-Custom-Steps-Development.md) | [WP Starter Command](10-WP-Starter-Command) |
