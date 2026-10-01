<?php

namespace App\Libraries\Sync;

use App\Libraries\Sync\Transform\TransformerRegistry;
use App\Models\SysSyncLogModel;
use Throwable;

/**
 * Pipeline sinkronisasi manual (tanpa cron):
 *
 *   log RUNNING -> FETCH_GET -> TRANSFORM -> DB_INSERT -> log final
 *
 * Status log:
 *   SUCCESS  — semua baris terbaca, ter-transform, dan ter-upsert
 *   WARNING  — selesai, tapi ada baris yang dilewati saat transform
 *   FAILED   — gagal di salah satu step (step_failed menandai step-nya)
 */
class SyncPipeline
{
    private HttpFetcher $fetcher;

    private TransformerRegistry $registry;

    private UpsertWriter $writer;

    public function __construct(
        ?HttpFetcher $fetcher = null,
        ?TransformerRegistry $registry = null,
        ?UpsertWriter $writer = null
    ) {
        $this->fetcher  = $fetcher ?? new HttpFetcher();
        $this->registry = $registry ?? new TransformerRegistry();
        $this->writer   = $writer ?? new UpsertWriter();
    }

    /**
     * @param array $task baris api_sync_tasks
     *
     * @return array{log: ?array, message: string}
     */
    public function run(array $task, string $triggerType = 'MANUAL_UI'): array
    {
        $logModel  = new SysSyncLogModel();
        $startedAt = microtime(true);

        $logId = $logModel->insert([
            'task_code'       => (string) $task['task_code'],
            'trigger_type'    => $triggerType,
            'status'          => 'RUNNING',
            'step_failed'     => 'NONE',
            'records_read'    => 0,
            'records_written' => 0,
            'duration_sec'    => 0,
            'error_message'   => null,
            'executed_at'     => date('Y-m-d H:i:s'),
            'finished_at'     => null,
        ], true);

        if (!$logId) {
            return [
                'log'     => array_merge($task, [
                    'status'          => 'FAILED',
                    'step_failed'     => 'NONE',
                    'records_read'    => 0,
                    'records_written' => 0,
                    'duration_sec'    => 0,
                    'error_message'   => 'Log sinkronisasi gagal disimpan.',
                    'executed_at'     => date('Y-m-d H:i:s'),
                    'finished_at'     => date('Y-m-d H:i:s'),
                ]),
                'message' => 'Log sinkronisasi gagal disimpan.',
            ];
        }

        $finalize = static function (
            string $status,
            string $stepFailed,
            int $read,
            int $written,
            ?string $errorMessage,
            string $message
        ) use ($logModel, $logId, $startedAt): array {
            $logModel->update($logId, [
                'status'          => $status,
                'step_failed'     => $stepFailed,
                'records_read'    => $read,
                'records_written' => $written,
                'duration_sec'    => round(microtime(true) - $startedAt, 2),
                'error_message'   => $errorMessage,
                'finished_at'     => date('Y-m-d H:i:s'),
            ]);

            return [
                'log'     => $logModel->find($logId),
                'message' => $message,
            ];
        };

        // --------------------------------------------------------- FETCH_GET
        try {
            $sourceRows = $this->fetcher->fetch($task);
        } catch (Throwable $exception) {
            return $finalize(
                'FAILED',
                'FETCH_GET',
                0,
                0,
                $exception->getMessage(),
                'Sync gagal (FETCH_GET): ' . $exception->getMessage()
            );
        }

        $read = count($sourceRows);

        // -------------------------------------------------------- TRANSFORM
        try {
            $transformer = $this->registry->forTask($task);
            $result      = $transformer->transform($sourceRows, $task);
        } catch (Throwable $exception) {
            return $finalize(
                'FAILED',
                'TRANSFORM',
                $read,
                0,
                $exception->getMessage(),
                'Sync gagal (TRANSFORM): ' . $exception->getMessage()
            );
        }

        $rowsToWrite = $result->rows();
        $skipped     = $result->skipped();

        if ($read > 0 && $rowsToWrite === []) {
            $error = self::summarizeErrors($result->errors());

            return $finalize(
                'FAILED',
                'TRANSFORM',
                $read,
                0,
                $error,
                'Semua baris (' . $read . ') dilewati saat transform. ' . $error
            );
        }

        // -------------------------------------------------------- DB_INSERT
        $written = 0;

        if ($rowsToWrite !== []) {
            try {
                $written = $this->writer->write(
                    (string) $task['target_table'],
                    $rowsToWrite,
                    max(1, (int) ($task['batch_size'] ?? 1500))
                );
            } catch (SyncWriteException $exception) {
                return $finalize(
                    'FAILED',
                    'DB_INSERT',
                    $read,
                    $exception->getWritten(),
                    $exception->getMessage(),
                    'Sync gagal (DB_INSERT): ' . $exception->getMessage()
                );
            } catch (Throwable $exception) {
                return $finalize(
                    'FAILED',
                    'DB_INSERT',
                    $read,
                    0,
                    $exception->getMessage(),
                    'Sync gagal (DB_INSERT): ' . $exception->getMessage()
                );
            }
        }

        // ---------------------------------------------------------- FINISHED
        $taskName = (string) ($task['task_name'] ?? $task['task_code']);

        if ($skipped > 0) {
            $error = self::summarizeErrors($result->errors());

            return $finalize(
                'WARNING',
                'NONE',
                $read,
                $written,
                $error,
                'Sync ' . $taskName . ' selesai dengan catatan: ' . $skipped . ' dari '
                . $read . ' baris dilewati. ' . $error
            );
        }

        return $finalize(
            'SUCCESS',
            'NONE',
            $read,
            $written,
            null,
            'Sync ' . $taskName . ' berhasil: ' . $written . ' baris di-upsert.'
        );
    }

    private static function summarizeErrors(array $errors): ?string
    {
        if ($errors === []) {
            return null;
        }

        $shown = array_slice($errors, 0, 10);
        $rest  = count($errors) - count($shown);

        $message = implode(' ', $shown);

        if ($rest > 0) {
            $message .= ' (+' . $rest . ' lainnya)';
        }

        return $message;
    }
}
