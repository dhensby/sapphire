---
title: Environment Types
summary: Configure your SilverStripe environment to define how your web application behaves.
icon: exclamation-circle
---
# Environment Types

SilverStripe knows three different environment types (or "modes"). Each of the modes gives you different tools
and behaviors. The environment is managed either through a [YML configuration file](../configuration) or in a 
[environment configuration file](../../getting_started/environment_management).

The definition of setting an environment type in a `mysite/_config/app.yml` looks like

```yml
	Director:
	  environment_type: 'dev'

```

```php
	define('SS_ENVIRONMENT_TYPE', 'dev');

```

### Dev

When developing your websites, adding page types or installing modules you should run your site in `dev`. In this mode
you will see full error back traces and view the development tools without having to be logged in as an administrator 
user.

[alert]
**dev mode should not be enabled long term on live sites for security reasons**. In dev mode by outputting back traces 
of function calls a hacker can gain information about your environment (including passwords) so you should use dev mode 
on a public server very carefully.
[/alert]

### Test Mode

Test mode is designed for staging environments or other private collaboration sites before deploying a site live.

In this mode error messages are hidden from the user and SilverStripe includes [api:BasicAuth] integration if you 
want to password protect the site. You can enable that but adding this to your `mysite/_config/app.yml` file:

```yml
```
```
	Only:
	  environment: 'test'
```
```
	BasicAuth:
	  entire_site_protected: true

```

All error messages are suppressed from the user and the application is in it's most *secure* state.

[alert]
Live sites should always run in live mode. You should not run production websites in dev mode.
[/alert]


## Checking Environment Type

You can check for the current environment type in [config files](../configuration) through the `environment` variant.

**mysite/_config/app.yml**
	---
```
	Only:
	  environment: 'live'
```
```
	MyClass:
		myvar: live_value
```
```
	Only:
	  environment: 'test'
```
```
	MyClass:
		myvar: test_value

```
certain functionality depending on the environment type. 

```php
	if(Director::isLive()) {
		// is in live
	} else if(Director::isTest()) {
		// is in test mode
	} else if(Director::isDev()) {
		// is in dev mode
	}

```