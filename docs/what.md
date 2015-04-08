<!--
currentMenu: what
title: What & Why
-->
# Introducing WP Starter

WP Starter is the easiest and fastest way to bootstrap WordPress sites entirely based on
[Composer](https://getcomposer.org/).

For the ones who never heard about Composer, or never used Composer in WordPress, the reading of [this site](http://composer.rarst.net) curated by [@Rarst](http://www.rarst.net) is strongly suggested.

Quoting from there:

> **Why Bother**
>
> Composer is dependency manager command line utility and accompanying infrastructure tools.
> It is made in PHP and for PHP. It can help you improve how you develop, share, make use of, host, and deploy your WordPress code and whole site stacks.

Managing WordPress whole site stacks with Composer has some issues, due to fact that WordPress doesn't natively support Composer.

## WordPress Package

First of all, to install WordPress via Composer we need a WordPress Package. In the past people managed their own package using Satis,
but nowadays the [WordPress package maintained by John P. Bloch](https://packagist.org/packages/johnpbloch/wordpress) became the *de-facto* standard
with its dozen of thousands of installations.

## Themes & Plugins Packages

The second issue that arises using WordPress with Composer is the ability to use WordPress plugins and themes as Composer packages.
This issue has been resolved thank to the work [outlandish](http://outlandish.com/) did creating [wpackagist](http://wpackagist.org/).

It is a mirror of the WordPress plugin and theme directories as a Composer repository.

# Why WP Starter

So the main issues about using Composer for WordPress whole-site stacks have already been solved, but there are issues left unsolved, that requires *manual* work, or that various developers have solved on their own with custom scripts and workflows.

Main aim of WP Starter is to give a way to address this issues in an automatic and reusable way.

The issues WP Starter addresses are:

 - When installed with Composer, **WordPress has its own directory**, this requires several steps ([described in Codex](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory))
 to make the site fully functional. WP Starter automates this steps.
 - Developers that use Composer to manage whole-site stacks, often put WP **content directory outside WordPress main folder**. This requires some configuration in `wp-config.php` that WP Starter does automatically.
 - Related to previous point, to make WordPress recognize **default themes**, they have to be moved in project content directory or their containing folder has to be registered as additional theme directory. WP Starter can do both things.
 - [**MU plugins**](https://codex.wordpress.org/Must_Use_Plugins) can be installed via Composer. The problem is that when doing so they are placed in their own folder so WordPress is unable to recognize them. WP Starter provides a way to make WordPress load MU plugins seamlessly from sub folders.
 - [**Dropins**](http://wpengineer.com/2500/wordpress-dropins/) are *special* WordPress files that override core WP components. These files must be placed in the WP content directory. WP Starter can automatically move them in the right place from a folder or from an url.
 - Composer has a simple, but powerful, way to install and manage two kinds of installations: for "development" and "production" **environments**. WordPress on its own has nothing similar, and developer just use different `wp-config.php` settings (database configuration, debug settings, urls and paths...) based on the target environment. This is all but standardized and it is hard to automatize. WP Starter addresses this issue with the usage of `.env` files.
