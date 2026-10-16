---
title: "FixedWidthPlugin"
description: "Adds fixed-width record files to the export plugin."
order: 100
generated: true
---

`Waypoint\Export\Fixed\FixedWidthPlugin` · final class · namespace [`Waypoint\Export\Fixed`](index.md)

```php
final class FixedWidthPlugin extends \Waypoint\Plugin\PluginBase
```

Extends `Waypoint\Plugin\PluginBase`

Adds fixed-width record files to the export plugin.

Register it after ExportPlugin: `$app->plugin(new FixedWidthPlugin())`. A Sheet
named `*.txt`, or created with `format: 'fixed'`, is then written by
FixedWidthWriter.

## Methods

### name()

```php
public function name(): string
```

Short unique name ('i18n'): the plan namespace for its route data, and the prefix in logs.

**Returns** `string`

### requires()

```php
public function requires(): array
```

**Returns** `list<class-string<Plugin>>` — Plugins that must be registered before this one.

### boot()

```php
public function boot(
    \Waypoint\Export\Cells $cells,
    \Waypoint\Export\Writers $writers,
    \Waypoint\Export\Fixed\FixedWidthOptions $options,
): void
```

Registers the writer twice in the export plugin's Writers registry: as `txt` and as `fixed`.

**Parameters**

- `$cells` `\Waypoint\Export\Cells`
- `$writers` `\Waypoint\Export\Writers`
- `$options` [`FixedWidthOptions`](FixedWidthOptions.md)
