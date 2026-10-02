<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regresi: pipeline lama benar-benar dibuang — endpoint task registry,
 * api sync, dan route monitoring lama harus PageNotFoundException (404).
 *
 * @internal
 */
final class LegacyRoutesRemovedTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testOldTaskRegistryRoutesAreGone()
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('tasks');
    }

    public function testOldApiSyncRoutesAreGone()
    {
        $this->expectException(PageNotFoundException::class);
        $this->post('api/sync/run/1');
    }

    public function testOldMonitoringRoutesAreGone()
    {
        // Route lama: /monitoring/(:segment) (show per-app) + check per-app.
        $this->expectException(PageNotFoundException::class);
        $this->get('monitoring/some-app');
    }

    public function testOldMonitoringCheckRouteIsGone()
    {
        $this->expectException(PageNotFoundException::class);
        $this->post('monitoring/check/some-app');
    }

    public function testNewMonitoringRouteStillWorks()
    {
        // Route baru tetap hidup: sesi kosong kena filter auth (redirect),
        // bukan 404.
        $this->get('monitoring')->assertRedirect();
    }

    public function testDashboardPlaceholderStillWorks()
    {
        $this->get('/')->assertRedirect();
    }
}
