<?php

namespace Waypoint\Export\Fixed;

use Waypoint\Export\{Cells, ExportPlugin, Writers};
use Waypoint\Plugin\PluginBase;

/**
 * Register after ExportPlugin: `$app->plugin(new FixedWidthPlugin())`. Adds the
 * fixed-width writer for `.txt` files and as `format: 'fixed'`.
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

    public function boot(Cells $cells, Writers $writers, FixedWidthOptions $options): void
    {
        $writers
            ->add(new FixedWidthWriter($cells, $options, 'txt'))
            ->add(new FixedWidthWriter($cells, $options, 'fixed'));
    }
}
