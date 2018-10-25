# Introduction to WP Starter



## What is this?

**WP Starter** is a [Composer plugin](https://getcomposer.org/doc/articles/plugins.md) that simplify the process to setup a WordPress website that uses Composer to manage all its *dependencies*.

With "dependencies" it is intended generic PHP libraries, but also WordPress plugins, themes and WordPress core itself.



## Why does it exist?

Composer is the de-facto standard dependency management tool for PHP. Pretty much all PHP projects, being them frameworks, applications or libraries, support Composer. All but WordPress.

**WordPress has no official support for Composer** and creating a website project with dependencies entirely based on Composer will require some effort and "bootstrap" work.

The main scope of this package is to simplify this process.

The additional scope of the project is to provide a mean to **configure WordPress by using [environment variables](https://en.wikipedia.org/wiki/Environment_variable)** instead of PHP constants.

The reason for this additional scope is that in professional development context it is more than common to have different environments for the same project, e.g. "development", "stage", and "production".

The standard "WordPress way" to do configuration via PHP constants makes having environment-aware configuration more complex than it needs to be. Other projects (not only PHP) have found environment variables to be the current solution for the issue, in fact, the usage of environment variables is one of the [Twelve-Factor App](https://12factor.net/) (collection of modern practices for web applications).



## How it works

WP Starter is a Composer plugin, which means that it can "listen" to Composer events and perform custom operations. Composer plugins extend Composer similarly to how WordPress plugins extend WordPress.

WP Starter listen to "install" and "update" Composer events to do a series of task that prepare the project to be a fully working WordPress site.

**Having a standard `composer.json` that requires both WordPress core package and WP Starter, and running `composer install` is everything is needed to have the installation ready**, including support for environment variables.



### A bit more on WP core package

Considering that WordPress has no official support for Composer, there's also no official way to integrate WordPress with Composer.

The way these days many people agree to do it is to treat WordPress as a dependency, like the others. And because WordPress, at this day, does not provide a repository of WordPress with support for Composer (basically having a `composer.json`) the most used package for the scope is the non-official package maintained by [John P. Bloch](https://johnpbloch.com/), that at the moment of writing has around 2.5 millions of downloads from [packagist.org](https://packagist.org/packages/johnpbloch/wordpress).

That said, WP Starter does **not** declare that package has a dependency, allowing to use custom packages or event to don't install WordPress via Composer at all.

The net effect is that to have  a **complete Composer-based WordPress website installation** it is required to just have a `composer.json` as simple as:

```json
{
    "name": "some-author/some-project",
    "require": {
        "johnpbloch/wordpress": "4.9.*",
        "wecodemore/wpstarter": "^3"
    }
}
```

and run `composer install`. 



## Requirements

- PHP 7.0+
- Composer