<?php

namespace Waypoint\Export\Fixed;

use Waypoint\Export\{Cells, ExportPlugin, Writers};
use Waypoint\Plugin\PluginBase;

/**
 * Adds fixed-width record files to the export plugin.
 *
 * Register it after ExportPlugin: `$app->plugin(new FixedWidthPlugin())`. A Sheet
 * named `*.txt`, or created with `format: 'fixed'`, is then written by
 * FixedWidthWriter.
 */
final class FixedWidthPlugin extends PluginBase
{
    public function name(): string
    {
        return 'export-fixed';
    }

    public function requires(): array
    {
        return [ExportPlugin::class];
    }

    /** Registers the writer twice in the export plugin's Writers registry: as `txt` and as `fixed`. */
    public function boot(Cells $cells, Writers $writers, FixedWidthOptions $options): void
    {
        $writers
            ->add(new FixedWidthWriter($cells, $options, 'txt'))
            ->add(new FixedWidthWriter($cells, $options, 'fixed'));
    }
}
