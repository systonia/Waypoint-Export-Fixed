<?php

namespace Waypoint\Export\Fixed;

use Waypoint\Container;
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

    public function boot(Container $container): void
    {
        $cells = $container->get(Cells::class);
        $options = $container->get(FixedWidthOptions::class);
        $container->get(Writers::class)
            ->add(new FixedWidthWriter($cells, $options, 'txt'))
            ->add(new FixedWidthWriter($cells, $options, 'fixed'));
    }
}
