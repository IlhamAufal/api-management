<?php

namespace App\Libraries\Sync\Transform;

interface TransformerInterface
{
    /**
     * Ubah source rows menjadi baris siap-insert untuk tabel target task.
     *
     * @param array $rows source rows dari HttpFetcher
     * @param array $task  baris api_sync_tasks
     */
    public function transform(array $rows, array $task): TransformResult;
}
