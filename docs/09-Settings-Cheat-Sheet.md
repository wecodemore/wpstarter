## Settings Cheat Sheet

Alphabetically ordered:

|          Key          | Description                                                  |       Default value        |
| :-------------------: | :----------------------------------------------------------- | :------------------------: |
|       autoload        | PHP file loaded before WP Starter its steps.<br />Path relative to root. | "./wpstarter-autoload.php" |
|    content-dev-op     | Operation to perform for "development content"<br />i.e. plugins and themes shipped with the project.<br />Valid values are "symlink", "copy" and "none".<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |         "symlink"          |
|    content-dev-dir    | Source folder for "development content".<br />Relative to root. |      "./content-dev"       |
|     command-steps     | Custom steps to be ran via WP Starter command.<br />Array of fully-qualified step class names.<br />Given classes must be autoloadable. |             []             |
|     custom-steps      | Array of custom steps to add to WP Starter.<br />Array of fully-qualified step class names.<br />Given classes must be autoloadable. |             []             |
|        dropins        | Array of dropins files to move to WP content folder.<br />Can be local path or remote URLs. |             []             |
|    early-hook-file    | PHP file where to add callbacks to very early hooks.<br />Must be path to file, relative to root.<br /> |            null            |
|   env-bootstrap-dir   | Folder where to look for env-specific bootstrap files.<br />Path to folder relative to root. |            null            |
|        env-dir        | Folder where to look for `.env` file.<br />Path to folder relative to root. |            "./"            |
|      env-example      | How to deal with `.env.example` file. Can be:<br />- `true` (copy default example file to root)<br />- `false` (do nothing)<br />- path, relative to root, to example file to copy.<br />- `"ask"` (user will be asked what to do) |            true            |
|       env-file        | Name of the `.env` file.<br />Will be searched inside `env-dir` |           ".env"           |
|    install-wp-cli     | Whether to install WP CLI from phar if necessary.            |            true            |
|     move-content      | Whether to move default themes and plugins<br />to project wp-content folder.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |           false            |
|   prevent-overwrite   | Array of paths that WP Starter has to not overwrite.<br />Path relative to root, might use glob patterns.<br />Can be set to`"ask"`, in which case<br /> WP Starter will ask confirmation before each overwrite. |             []             |
| register-theme-folder | Whether to register default themes for the project.<br />When `true`, will force `move-content` to `false`.<br />Can be set to`"ask"`, in which case<br />WP Starter will ask user what to do. |           false            |
|      require-wp       | Whether to check for WP package being required.              |            true            |
|        scripts        | Array of script to run either before or after steps.<br />An object where key is in the format:<br /> `"pre-{$stepname}"` or `"post-{$stepname}"`<br />and value is either a function or a static method.<br />Functions (or methods) must be autoloadable. |             []             |
|      skip-steps       | Array of step names to skip.                                 |             []             |
|     templates-dir     | Folder where to look for custom file templates.<br />Relative to root. |            null            |
|    unknown-dropins    | How to deal with non-standard dropins.<br />Can be:<br />- `true` just install them<br />- `false` just skip them<br />- `"ask"` will ask the user what to do |           false            |
