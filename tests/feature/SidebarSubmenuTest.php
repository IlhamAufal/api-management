<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Sub-menu sidebar "Monitoring" (plan: dua halaman terselubung
 * freshness & workflow dijadikan submenu eksplisit):
 *
 * - /monitoring (dan /monitoring/* selain workflow) → item "Freshness Monitor" aktif.
 * - /monitoring/workflow* → item "Alur Workflow" aktif.
 * - Halaman lain (dashboard, logs, dst.) → parent Monitoring tidak aktif.
 *
 * @internal
 */
final class SidebarSubmenuTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    public function testSidebarRendersBothMonitoringSubmenuItems()
    {
        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('Freshness Monitor', $result);
        $this->assertBodySee('monitoring/workflow', $result);
        // Item induk sekarang berupa tombol collapse, bukan link langsung.
        $this->assertBodySee('Alur Workflow', $result);
    }

    public function testFreshnessSubmenuItemIsActiveOnMonitoringPage()
    {
        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $body = $result->response()->getBody();

        $this->assertTrue(
            (bool) preg_match('/href="[^"]*monitoring"[^>]*class="[^"]*menu-dropdown-item-active[^"]*"/', $body),
            'Item "Freshness Monitor" harus aktif (menu-dropdown-item-active) di halaman monitoring.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="[^"]*monitoring\/workflow"[^>]*class="[^"]*menu-dropdown-item-active[^"]*"/',
            $body,
            'Item "Alur Workflow" tidak boleh aktif di halaman monitoring.'
        );
    }

    public function testWorkflowSubmenuItemIsActiveOnWorkflowPage()
    {
        $result = $this->withSession($this->authSession())->get('monitoring/workflow');

        $result->assertOK();
        $body = $result->response()->getBody();

        $this->assertTrue(
            (bool) preg_match('/href="[^"]*monitoring\/workflow"[^>]*class="[^"]*menu-dropdown-item-active[^"]*"/', $body),
            'Item "Alur Workflow" harus aktif (menu-dropdown-item-active) di halaman workflow.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="[^"]*monitoring"[^>]*class="[^"]*menu-dropdown-item-active[^"]*"/',
            $body,
            'Item "Freshness Monitor" tidak boleh aktif di halaman workflow.'
        );
    }

    public function testParentMonitoringStaysActiveOnWorkflowPage()
    {
        // Parent memakai $page === 'monitoring' — workflow juga memakai page itu,
        // sehingga submenu tetap ter-highlight saat berada di dalamnya.
        $result = $this->withSession($this->authSession())->get('monitoring/workflow');

        $result->assertOK();
        $this->assertBodySee('menu-item-active', $result);
    }
}
