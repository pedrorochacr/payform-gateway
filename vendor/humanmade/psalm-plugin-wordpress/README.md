<h1>WordPress plugin for Psalm</h1>

[![Packagist](https://img.shields.io/packagist/v/humanmade/psalm-plugin-wordpress.svg)](https://packagist.org/packages/humanmade/psalm-plugin-wordpress)
[![Packagist](https://img.shields.io/packagist/dt/humanmade/psalm-plugin-wordpress.svg)](https://packagist.org/packages/humanmade/psalm-plugin-wordpress)

Write type-safe WordPress code.

This [Psalm](https://psalm.dev/) plugin provides all WordPress and WP CLI stubs, so your WordPress based project or plugin will have type information for calls to WordPress APIs. This ensures your WordPress plugin or theme has less bugs!

- [x] Stubs for all of WordPress Core
- [x] Stubs for WP CLI
- [x] Types for `apply_filters` return values.
- [x] Types for `add_filter` / `add_action`
- [x] Configuration options to use your own stubs

## Installation

Please refer to the [full Psalm documentation](https://psalm.dev/quickstart) for a more detailed guide on introducing Psalm into your project.

After Psalm is installed, install this package and enable the plugin:

```shell
composer require --dev humanmade/psalm-plugin-wordpress
./vendor/bin/psalm-plugin enable humanmade/psalm-plugin-wordpress
```

## Configuration

If you follow the installation instructions, the `psalm-plugin` command will add this plugin configuration to the `psalm.xml` configuration file.

```xml
<?xml version="1.0"?>
<psalm xmlns="https://getpsalm.org/schema/config">
	<!-- project configuration -->

	<plugins>
		<pluginClass class="PsalmWordPress\Plugin" />
	</plugins>
</psalm>
```

If you do not want to use the default WordPress stubs, which are part of this plugin, `useDefaultStubs` must be set to `false`:

```xml
<pluginClass class="PsalmWordPress\Plugin">
	<useDefaultStubs value="false" />
</pluginClass>
```

If you do not want to use the default WordPress hooks, which are part of this plugin, `useDefaultHooks` must be set to `false`:

```xml
<pluginClass class="PsalmWordPress\Plugin">
	<useDefaultHooks value="false" />
</pluginClass>
```

You can also provide custom hooks:

```xml
<pluginClass class="PsalmWordPress\Plugin">
	<hooks>
		<directory name="some/dir/hooks" recursive="true" />
		<directory name="/absolute/other/dir/hooks" />
		<file name="my-special-hooks/actions.json" />
	</hooks>
</pluginClass>
```

Further details about plugins can be found on [Psalm's website](https://psalm.dev/docs/running_psalm/plugins/using_plugins/).

## Interested in contributing?

Feel free to open a PR to fix bugs or add features!

In addition, have a look at Psalm's [contribution guidelines](https://github.com/vimeo/psalm/blob/master/CONTRIBUTING.md).

## Who made this

Created by @joehoyle, maintained by the Psalm community.
