# Change Log for WPStarter

## [Unreleased]
* Templates: Add EOF lines. See [#67].

## [2.4.2] - 2017-06-29
* Define `WP_CONTENT_URL` only on single site. See [#48], [#57].
* Minor code standards fix.
* Update docs to adapt to change on `johnpbloch/wordpress`.
* Expicitly globalize `$table_prefix` in `wp-config.php`. See [#61].

## [2.4.1] - 2017-02-21
* Removed strict types declaration for PHP 5 compat.

## [2.4.0] - 2017-02-21

* Cast whatever we get from `get_option(uninstall_plugins)` to an array, to avoid throwing a warning. See [#36].
* Fix variable syntax for `.env` file template. See [#38].
* Fix RuntimeException because of `DB_NAME`, `DB_USER`, `DB_PASSWORD` not set. See [#49].
* Improve `Helpers::addHook()` by using WordPress function when available and loading `plugin.php` earlier on WP 4.7+. See [#50].
* Add WP CLI step that adds a `wp-cli.yml` file in the project root. See [#33].
* Code styles fixes.
* Declare strict types in all files.

## [2.3.2] - 2016-07-28

* Changed bootstrap line in `index.php` template to be similar to what WP core uses. This avoid issues with scripts parsing `index.php` with regular expression. Props [Alain Schlesser], see [#35].

## [2.3.1] - 2016-07-14

* `wp-content`-related constants are defined only when actually different from default, otherwise network installation screen will complain for no reason.
* Documentation improvement and fixes, props [Alain Schlesser].

## [2.3.0] - 2016-07-14

* Make `WPSTARTER_PATH` available in `wp-config.php` (see [#30]).

## [2.2.5] - 2016-04-13

* Add `Setup::runAsRoot` script to `post-update-cmd` to ensure installation script on update when WP Starter is used as root package.
* Update "Quick Start" example to include `Setup::run` script to `post-update-cmd`.

## [2.2.4] - 2016-03-06

* Require wpackagist with `https`. Only relevant when used as root package.

## [2.2.3] - 2016-02-29

* Removed `WP_LANG` and `FORCE_SSL_LOGIN`, props [Gary Jones].

## [2.2.2] - 2016-01-18

* Fix MuLoader throws notice (see [#18]), props [Alain Schlesser].
* Remove duplicate settings from `.env.example` template, props [Gary Jones].

## [2.2.1] - 2015-10-29

* Salt keys are not forced to be unique per environment by default.

  This has proven to be an issue is some cases, even if issue was not consistently reproducible.

  As always salts can be set via environment vars.

## [2.2.0] - 2015-09-28

* Updated `Dotenv` version.
* Add possibility to use custom name `.env` file.
* General improvements to environment variables handling.

## [2.1.3] - 2015-07-15

* Add missing constants in `Env` `$isString`. Fixed [#10]

## [2.1.2] - 2015-07-13

* Fix CS and minor issues on `wp-config` template.

## [2.1.1] - 2015-06-30

* Fix HTTPS when a load balancer is in use.

## [2.1.0] - 2015-05-12

* Better folder handling: now all paths are internally managed as relative to root. Nothing changed for API.

## [2.0.2] - 2015-05-11

* Fixed a bug in `Config` class.

## [2.0.1] - 2015-04-13

* Fixed bug in `Env::normalise()` method.

## [2.0.0] - 2015-04-08

* Massive refactoring: code is now more modular and the entire workflow is organized into "steps".
* Content folder is not moved anymore, but an additional theme folder is registered. As per discussion in [#2]
* Configurations discussed in [#3] is now available.
* Documenation added in gh-pages branch, see [https://wecodemore.github.io/wpstarter/]

## [1.3.4] - 2015-03-18

* Fixed issue with MuLoader on installing.

## [1.3.3] - 2015-03-10

* Fixed PHP 5.3 compatibility issue.

## [1.3.2] - 2015-03-06

* Handle edge cases for folders to git ignore.

## [1.3.1] - 2015-03-06

* Hotfix for `buildFile` error.

## [1.3.0] - 2015-03-06

* Before creating `.gitignore` user is required of a confirm, even if no `.gitignore` exists. (If it exists creation is skipped at all, a warning is printed to console).
* When `.gitignore` is created it contains:
	* `wp-config.php`
	* `.env`
	* WordPress folder
	* `wp-content` folder
	* vendor folder
	* a bunch of common irrelevant files
* Improvements to console messages and a new class to handle them.
* General refactoring.

## [1.2.1] - 2015-03-05

* Better `.gitignore` flow.

## [1.2.0] - 2015-03-05

* When the server that install package is not the same of the one that uses it, absolute paths fail. Always use relative paths.

## [1.1.0] - 2015-03-05

* Added README.

## 1.0.0 - 2015-03-04

* Initial release.

[https://wecodemore.github.io/wpstarter/]: https://wecodemore.github.io/wpstarter/

[Alain Schlesser]: https://github.com/schlessera
[Gary Jones]: https://github.com/GaryJones

[#67]: https://github.com/wecodemore/wpstarter/issues/67
[#61]: https://github.com/wecodemore/wpstarter/issues/61
[#57]: https://github.com/wecodemore/wpstarter/issues/57
[#48]: https://github.com/wecodemore/wpstarter/issues/48
[#38]: https://github.com/wecodemore/wpstarter/issues/38
[#36]: https://github.com/wecodemore/wpstarter/issues/36
[#35]: https://github.com/wecodemore/wpstarter/issues/35
[#30]: https://github.com/wecodemore/wpstarter/issues/30
[#18]: https://github.com/wecodemore/wpstarter/issues/18
[#10]: https://github.com/wecodemore/wpstarter/issues/10
[#3]: https://github.com/wecodemore/wpstarter/issues/3
[#2]: https://github.com/wecodemore/wpstarter/issues/2

[Unreleased]: https://github.com/wecodemore/wpstarter/compare/2.4.2...HEAD
[2.4.2]: https://github.com/wecodemore/wpstarter/compare/2.4.1...2.4.2
[2.4.1]: https://github.com/wecodemore/wpstarter/compare/2.4.0...2.4.1
[2.4.0]: https://github.com/wecodemore/wpstarter/compare/2.3.2...2.4.0
[2.3.2]: https://github.com/wecodemore/wpstarter/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/wecodemore/wpstarter/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/wecodemore/wpstarter/compare/2.2.5...2.3.0
[2.2.5]: https://github.com/wecodemore/wpstarter/compare/2.2.4...2.2.5
[2.2.4]: https://github.com/wecodemore/wpstarter/compare/2.2.3...2.2.4
[2.2.3]: https://github.com/wecodemore/wpstarter/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/wecodemore/wpstarter/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/wecodemore/wpstarter/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/wecodemore/wpstarter/compare/2.1.3...2.2.0
[2.1.3]: https://github.com/wecodemore/wpstarter/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/wecodemore/wpstarter/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/wecodemore/wpstarter/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/wecodemore/wpstarter/compare/2.0.2...2.1.0
[2.0.2]: https://github.com/wecodemore/wpstarter/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/wecodemore/wpstarter/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/wecodemore/wpstarter/compare/1.3.4...2.0.0
[1.3.4]: https://github.com/wecodemore/wpstarter/compare/1.3.3...1.3.4
[1.3.3]: https://github.com/wecodemore/wpstarter/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/wecodemore/wpstarter/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/wecodemore/wpstarter/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/wecodemore/wpstarter/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/wecodemore/wpstarter/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/wecodemore/wpstarter/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/wecodemore/wpstarter/compare/1.0.0...1.1.0
