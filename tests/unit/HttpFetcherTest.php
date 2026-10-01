<?php

use App\Libraries\Sync\HttpFetcher;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test normalisasi response HttpFetcher (tanpa network).
 *
 * @internal
 */
final class HttpFetcherTest extends CIUnitTestCase
{
    public function testPlainListIsReturnedAsIs()
    {
        $rows = [['code' => 'A'], ['code' => 'B']];

        $this->assertSame($rows, HttpFetcher::normalizeResponse($rows));
    }

    public function testDataEnvelopeIsUnwrapped()
    {
        $rows = [['code' => 'A']];

        $this->assertSame($rows, HttpFetcher::normalizeResponse(['data' => $rows]));
        $this->assertSame($rows, HttpFetcher::normalizeResponse(['results' => $rows]));
        $this->assertSame($rows, HttpFetcher::normalizeResponse(['items' => $rows]));
    }

    public function testODataV4ValueEnvelopeIsUnwrapped()
    {
        $rows = [['Product' => 'P-1'], ['Product' => 'P-2']];

        $this->assertSame($rows, HttpFetcher::normalizeResponse(['value' => $rows]));
        $this->assertFalse(HttpFetcher::isODataEnvelope(['value' => $rows]));
    }

    public function testODataV2DResultsEnvelopeIsUnwrapped()
    {
        $decoded = ['d' => ['results' => [['Product' => 'P-1']]]];

        $this->assertSame([['Product' => 'P-1']], HttpFetcher::normalizeResponse($decoded));
        $this->assertTrue(HttpFetcher::isODataEnvelope($decoded));
    }

    public function testSingleEntityBecomesOneRow()
    {
        $decoded = ['d' => ['Product' => 'P-1']];

        $this->assertSame([['Product' => 'P-1']], HttpFetcher::normalizeResponse($decoded));
    }

    public function testBareObjectBecomesOneRow()
    {
        $decoded = ['material_number' => 'M-1'];

        $this->assertSame([['material_number' => 'M-1']], HttpFetcher::normalizeResponse($decoded));
    }

    public function testErrorEnvelopeThrows()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API mengembalikan error: Not found');

        HttpFetcher::normalizeResponse(['error' => ['message' => 'Not found']]);
    }

    public function testNonArrayResponseThrows()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Response API bukan objek/array JSON.');

        HttpFetcher::normalizeResponse('oops');
    }
}
