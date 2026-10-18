<?php

namespace Waypoint\Export\Fixed\Tests;

use RuntimeException;
use Waypoint\Export\{ExportPlugin, Writers};
use Waypoint\Export\Fixed\{FixedWidthOptions, FixedWidthPlugin};
use Waypoint\OpenAPI\OpenAPIGenerator;
use Waypoint\Testing\TestCase;

final class PluginTest extends TestCase
{
    public function testTheSubPluginRegistersItsWriterWithTheBase(): void
    {
        $app = $this->app();
        $app->plugin(new ExportPlugin());
        $app->plugin(new FixedWidthPlugin());
        $app->configure(fn (FixedWidthOptions $o) => $o->encoding = 'UTF-8');
        $app->attach([SatzController::class]);

        $this->get('/lieferung.txt')
            ->assertOk()
            ->assertContentType('text/plain')
            ->assertHeader('Content-Disposition', 'attachment; filename="lieferung.txt"; filename*=UTF-8\'\'lieferung.txt')
            ->assertBody("00000042Müller       1234,5\r\n");
        $this->get('/lieferung.dat')->assertOk()->assertBody("00000001A" . str_repeat(' ', 17) . "1\r\n");

        $this->assertTrue($app->getContainer()->get(Writers::class)->has('fixed'));
        $content = (new OpenAPIGenerator($app->getRouter()))->generate()['paths']['/lieferung.txt']['get']['responses']['200']['content'];
        $this->assertArrayHasKey('text/plain', $content);
    }

    public function testItMustComeAfterTheBasePlugin(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires Waypoint\Export\ExportPlugin');
        $this->app()->plugin(new FixedWidthPlugin());
    }
}
