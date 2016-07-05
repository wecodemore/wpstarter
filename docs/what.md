<!--
currentMenu: what
title: What & Why
-->
# Introducing WP Starter

WP Starter is the easiest and fastest way to bootstrap WordPress sites entirely based on
[Composer](https://getcomposer.org/).

For those who have never heard of Composer, or never used Composer with WordPress, it is strongly suggested to read [this site](http://composer.rarst.net) curated by [@Rarst](http://www.rarst.net).

Quoting from Rarst's site:

> **Why Bother**
>
> Composer is dependency manager command line utility and accompanying infrastructure tools.
> It is made in PHP and for PHP. It can help you improve how you develop, share, make use of, host, and deploy your WordPress code and whole site stacks.

Managing WordPress whole-site stacks with Composer has some issues, due to the fact that WordPress doesn't natively support Composer.

## WordPress Package

First of all, to install WordPress via Composer we need a WordPress Package. In the past, people hosted their own packages using Satis,
but nowadays the [WordPress package maintained by John P. Bloch](https://packagist.org/packages/johnpbloch/wordpress) became the *de facto* standard
with its dozens of thousands of installations.

## Themes & Plugins Packages

The second issue you might encounter using WordPress with Composer is trying to use WordPress plugins and themes as Composer packages.
This is now easily done thanks to the work [outlandish](http://outlandish.com/) did creating [wpackagist](https://wpackagist.org/).

It is a mirror of the official wordpress.org plugins and themes repository as a Composer repository.

# Why WP Starter

So the main issues involved with using Composer for WordPress whole-site stacks have already been solved. But there are some issues that still require *manual* work, or that various developers have solved on their own with custom scripts and workflows.

The main aim of WP Starter is to give you a way of addressing these issues in an automated and reusable way.

The issues WP Starter addresses are:

 - When installed with Composer, **WordPress gets its own directory**. This requires several steps ([as described in Codex](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory))
 to make the site fully functional. WP Starter automates these steps.
 - Developers using Composer to manage whole-site stacks often put WP **content directory outside WordPress main folder**. This requires some configuration in `wp-config.php` that WP Starter includes automatically.
 - Related to the previous point, to make WordPress recognize **default themes**, they have to be moved into the project content directory or their containing folder has to be registered as an additional theme directory. WP Starter can take care of either one of these.
 - [**MU plugins**](https://codex.wordpress.org/Must_Use_Plugins) can be installed via Composer. The problem is that when doing so they are placed in their own folder, so WordPress is unable to recognize them. WP Starter provides a way to make WordPress load MU plugins seamlessly from subfolders.
 - [**Dropins**](http://wpengineer.com/2500/wordpress-dropins/) are *special* WordPress files that override core WP components. These files must be placed into the WP content directory. WP Starter can automatically move them to the right place from a folder or from an url.
 - Composer has a simple but powerful way to install and manage two kinds of installations: "development" as well as "production" **environments**. WordPress on its own has nothing similar, and developers just use different `wp-config.php` settings (database configuration, debug settings, urls and paths...) based on the target environment. This is not standardized and is hard to automate. WP Starter addresses this issue through the usage of `.env` files.
