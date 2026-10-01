<?php

namespace App\Libraries\Sync;

use RuntimeException;

/**
 * Fetch JSON dari source_endpoint via cURL GET (tanpa API key/auth).
 *
 * Response dinormalisasi ke list of rows dan mendukung beberapa bentuk
 * envelope umum: array polos, {data:[...]}, {value:[...]} (OData v4),
 * {d:{results:[...]}} (OData v2), serta objek tunggal.
 */
class HttpFetcher
{
    private int $timeoutSec;

    private int $maxPages;

    public function __construct(int $timeoutSec = 30, int $maxPages = 100)
    {
        $this->timeoutSec = $timeoutSec;
        $this->maxPages    = max(1, $maxPages);
    }

    /**
     * @param array $task baris api_sync_tasks
     *
     * @return array list of source rows (assoc)
     *
     * @throws RuntimeException bila endpoint tidak valid / HTTP error / bukan JSON
     */
    public function fetch(array $task): array
    {
        $endpoint = trim((string) ($task['source_endpoint'] ?? ''));

        if ($endpoint === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Source endpoint tidak valid: "' . $endpoint . '".');
        }

        $batchSize = max(1, (int) ($task['batch_size'] ?? 1500));
        // Endpoint OData perlu $skip/$top; selain itu satu request saja.
        $paging    = stripos($endpoint, 'odata') !== false;
        $rows      = [];
        $skip      = 0;

        for ($page = 0; $page < $this->maxPages; $page++) {
            $url = $endpoint;
            if ($paging || $page > 0) {
                $url = $this->appendQuery($endpoint, ['$top' => $batchSize, '$skip' => $skip]);
            }

            $decoded  = $this->getJson($url);
            $pageRows = self::normalizeResponse($decoded);
            $count    = count($pageRows);

            if (self::isODataEnvelope($decoded)) {
                $paging = true;
            }

            $rows = array_merge($rows, $pageRows);

            if (!$paging || $count < $batchSize || $count === 0) {
                break;
            }

            $skip += $count;
        }

        return $rows;
    }

    /**
     * Normalisasi response JSON menjadi list of rows (pure, tanpa network).
     *
     * @param mixed $decoded hasil json_decode(..., true)
     *
     * @throws RuntimeException bila payload bukan array/objek atau berisi envelope error
     */
    public static function normalizeResponse($decoded): array
    {
        if (!is_array($decoded)) {
            throw new RuntimeException('Response API bukan objek/array JSON.');
        }

        if (self::isList($decoded)) {
            return $decoded;
        }

        if (isset($decoded['error'])) {
            $error = $decoded['error'];
            $message = is_array($error)
                ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE))
                : (string) $error;

            throw new RuntimeException('API mengembalikan error: ' . $message);
        }

        foreach (['data', 'value', 'results', 'items', 'records'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                return self::isList($decoded[$key]) ? $decoded[$key] : array_values($decoded[$key]);
            }
        }

        if (isset($decoded['d']) && is_array($decoded['d'])) {
            $d = $decoded['d'];

            if (isset($d['results']) && is_array($d['results'])) {
                return self::isList($d['results']) ? $d['results'] : array_values($d['results']);
            }

            if (self::isList($d)) {
                return $d;
            }

            // Objek entity tunggal.
            return [$d];
        }

        // Objek tunggal tanpa envelope: perlakukan sebagai 1 baris.
        // Keamanannya ada di tahap transform (primary key wajib terisi).
        return [$decoded];
    }

    public static function isODataEnvelope($decoded): bool
    {
        return is_array($decoded) && ! self::isList($decoded) && isset($decoded['d']);
    }

    private function getJson(string $url)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT  => min(10, $this->timeoutSec),
            CURLOPT_TIMEOUT        => $this->timeoutSec,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'MD-Bridge/1.0',
        ]);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $errno !== 0) {
            throw new RuntimeException('cURL gagal mengambil data: ' . ($error !== '' ? $error : 'error #' . $errno));
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Endpoint merespons HTTP ' . $status . '.');
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Response bukan JSON valid: ' . json_last_error_msg());
        }

        return $decoded;
    }

    private function appendQuery(string $url, array $params): string
    {
        $query = http_build_query($params);

        return $url . (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    private static function isList(array $array): bool
    {
        $index = 0;

        foreach ($array as $key => $value) {
            if ($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }
}
