---
title: Introduction
nav_order: 1
---

# Introduction
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

- TOC
{:toc}

## What is this?

**WP Starter** is a [Composer plugin](https://getcomposer.org/doc/articles/plugins.md) that simplifies the setup process of a WordPress website that uses Composer to manage all its *dependencies*.

"Dependencies" refers to generic PHP libraries, WordPress plugins, themes and WordPress core itself.



## Why does it exist?

Composer is the de-facto standard dependency management tool for PHP. Pretty much all PHP projects, be they frameworks, applications or libraries, support Composer. All but WordPress.

**WordPress has no official support for Composer** and creating a website project with dependencies entirely based on Composer will require some effort and "bootstrap" work.

The main scope of WP Starter is to simplify this process.

The additional scope of the project is to provide a mean to **configure WordPress by using [environment variables](https://en.wikipedia.org/wiki/Environment_variable)** instead of PHP constants.

The reason for this additional scope is that in a professional development context it is common to have different environments for the same project, e.g. "development", "stage", and "production".

The standard "WordPress way" to do configuration via PHP constants makes having environment-aware configuration more complex than it needs to be. Other projects (not only PHP) have found environment variables to be the current solution for the issue, in fact, the usage of environment variables is one of the [Twelve-Factor App](https://12factor.net/) (collection of modern practices for web applications).



## How it works

WP Starter is a Composer plugin, which means that it can "listen" to Composer events and perform custom operations. Composer plugins extend Composer similarly to how WordPress plugins extend WordPress.

WP Starter listens to "install" and "update" Composer events to do a series of tasks that prepare the project to be a fully working WordPress site.

A standard `composer.json` file that requires both a WordPress core package and WP Starter, like the following:

```json
{
    "name": "some-author/some-project",
    "require": {
        "roots/wordpress": ">=6.1",
        "wecodemore/wpstarter": "^3"
    }
}
```

followed by `composer install` is **everything** required to have a complete Composer-based WordPress website installation.

The snippet above makes use of the non-official WordPress package maintained by [Roots](https://roots.io/), that at the moment of writing is the most popular WordPress core package on [packagist.org](https://packagist.org/packages/roots/wordpress) with several millions of downloads ([![Roots WordPress Total Downloads](https://img.shields.io/packagist/dt/roots/wordpress.svg?color=9FC65D&labelColor=3C3D46&style=flat-square)](https://packagist.org/packages/roots/composer)).

Of course, this is the bare minimum. WP Starter is quite powerful and flexible and rest of documentation will describe how to configure and make the most out of it.



## Requirements

- PHP 7.4+
- Composer 2.0+


------

**Next:** [Environment-Variables](02-Environment-Variables.md)

---

- ***Introduction***
- [Environment-Variables](02-Environment-Variables.md)
- [WordPress Integration](03-WordPress-Integration.md)
- [WP Starter Configuration](04-WP-Starter-Configuration.md)
- [WP Starter Steps](05-WP-Starter-Steps.md)
- [A Commented Sample `composer.json`](06-A-Commented-Sample-Composer-Json.md)
- [WP CLI Commands](07-WP-CLI-Commands.md)
- [Custom Steps Development](08-Custom-Steps-Development.md)
- [Settings Cheat Sheet](09-Settings-Cheat-Sheet.md)
- [Command-line Interface](10-Command-Line-Interface.md)

