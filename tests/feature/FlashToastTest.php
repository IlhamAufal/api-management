<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Flash message (redirect()->with) harus tampil sebagai toast lewat
 * components/toast.php — bukan banner inline di atas konten.
 *
 * Toast component membaca key `flash_success` / `flash_error` dan
 * me-render-nya sebagai bootstrap `message:` / `type:` pada script Alpine.
 *
 * @internal
 */
final class FlashToastTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    /** Wrapper banner flash inline lama (sudah dihapus dari view). */
    private const BANNER = 'mb-4 flex items-start gap-2';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    public function testCheckCellFlashSuccessRendersAsToastNotBanner()
    {
        $id  = $this->insertWatchedTable();
        $npd = $this->sourceId('npd');

        $post = $this->withSession($this->authSession())
            ->post('monitoring/check-cell/' . $id . '/' . $npd);
        $post->assertRedirect();
        $post->assertSessionHas('flash_success');

        // Bawa $_SESSION hasil request POST (berisi flash + auth)
        // ke request GET berikutnya — meniru lifecycle redirect CI.
        $this->withSession();
        $result = $this->get('monitoring/source/npd');

        $result->assertOK();
        // Pesan muncul di bootstrap toast (json_encode di dalam script).
        // Kutip label ikut ter-escape oleh json_encode: \"FX Orders\".
        $this->assertBodySee('Check \"FX Orders\" di NPD (RDS) selesai: 1 sel (', $result);
        // Banner inline lama tidak lagi dirender.
        $this->assertBodyNotSee(self::BANNER, $result);
    }

    public function testFlashErrorRendersAsErrorToastOnWatchedTables()
    {
        $session            = $this->authSession();
        $session['flash_error'] = 'Gagal menyimpan konfigurasi dari test.';
        // Penanda flashdata CI (dipasang oleh redirect()->with()):
        // nilainya string 'new' — marker int justru dianggap flash lama.
        $session['__ci_vars']   = ['flash_error' => 'new'];

        $result = $this->withSession($session)->get('watched-tables');

        $result->assertOK();
        $body = $result->response()->getBody();

        $this->assertTrue(
            (bool) preg_match('/type: "error",\s*message: "Gagal menyimpan konfigurasi dari test\./', $body),
            'Toast tidak merender flash_error dengan tipe error.'
        );
        $this->assertBodyNotSee(self::BANNER, $result);
    }

    public function testPageWithoutFlashRendersHiddenToastAndNoBanner()
    {
        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('message: ""', $result);
        $this->assertBodyNotSee(self::BANNER, $result);
    }
}
