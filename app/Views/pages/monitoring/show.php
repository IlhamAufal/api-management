<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMeta = static function ($status) {
    $map = [
        'SUCCESS'       => ['label' => 'Success', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
        'FAILED'        => ['label' => 'Failed', 'class' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'],
        'WARNING'       => ['label' => 'Warning', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'RUNNING'       => ['label' => 'Running', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'UNKNOWN'       => ['label' => 'No cron data', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
        'NOT_CONNECTED' => ['label' => 'Not connected', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
    ];
    return $map[$status] ?? $map['UNKNOWN'];
};
$formatDate = static function ($value) {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d M Y, H:i:s', $timestamp) : '—';
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <a href="<?= base_url('monitoring') ?>" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">&larr; Kembali ke aplikasi</a>

  <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 sm:flex-row sm:items-start sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
    <div><p class="font-mono text-xs font-medium uppercase tracking-wide text-brand-500"><?= esc($app['app_code']) ?></p><h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= esc($app['app_name']) ?></h1><p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400"><?= esc($app['description'] ?: 'Status sinkronisasi dan riwayat cron aplikasi.') ?></p></div>
    <?php $appMeta = $statusMeta($summary['operational_status']); ?>
    <div class="text-right"><span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold <?= $appMeta['class'] ?>"><?= esc($appMeta['label']) ?></span><p class="mt-2 font-mono text-xs text-gray-400"><?= esc($app['database_name'] ?: '—') ?></p></div>
  </div>

  <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500 dark:text-gray-400">Telemetry tersedia</p><p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90"><?= (int) $summary['reported_tables'] ?>/<?= count($tables) ?></p><p class="mt-1 text-xs text-gray-400">tabel memiliki log CRON</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500 dark:text-gray-400">Tabel gagal</p><p class="mt-2 text-2xl font-bold <?= (int) $summary['failed_tables'] > 0 ? 'text-error-600 dark:text-error-400' : 'text-gray-800 dark:text-white/90' ?>"><?= (int) $summary['failed_tables'] ?></p><p class="mt-1 text-xs text-gray-400">berdasarkan cron terakhir</p></div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500 dark:text-gray-400">Cron terakhir</p><p class="mt-2 text-lg font-bold text-gray-800 dark:text-white/90"><?= esc($formatDate($summary['last_cron_at'])) ?></p><p class="mt-1 text-xs text-gray-400">telemetry terbaru aplikasi</p></div>
  </section>

  <div class="mt-6">
    <?= $this->include('components/workflow_canvas', ['workflow' => $workflow]) ?>
  </div>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Status Tabel</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Status terkini berasal dari eksekusi CRON terakhir untuk setiap tabel.</p></div>
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1050px] text-left">
        <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400"><tr><th class="px-5 py-3 font-medium">Tabel</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Cron terakhir</th><th class="px-5 py-3 text-right font-medium">Read</th><th class="px-5 py-3 text-right font-medium">Written</th><th class="px-5 py-3 font-medium">Durasi</th><th class="px-5 py-3 font-medium">Schedule</th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php foreach ($tables as $table): ?>
            <?php $meta = $statusMeta($table['operational_status']); $lastCron = $table['last_cron']; ?>
            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
              <td class="px-5 py-4"><p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($table['table_name']) ?></p><code class="mt-1 block text-xs text-gray-400"><?= esc($table['table_code']) ?></code></td>
              <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><?= esc($meta['label']) ?></span></td>
              <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($lastCron['finished_at'] ?? $lastCron['executed_at'] ?? null)) ?></td>
              <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $lastCron ? number_format((int) $lastCron['records_read']) : '—' ?></td>
              <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $lastCron ? number_format((int) $lastCron['records_written']) : '—' ?></td>
              <td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300"><?= $lastCron ? number_format((float) $lastCron['duration_sec'], 2) . 's' : '—' ?></td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['cron_expression'] ?: '—') ?></code></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white/90">Riwayat Cronjob</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Maksimal 50 eksekusi CRON terbaru. Trigger manual tidak dicampur ke riwayat ini.</p></div>
    <?php if (empty($history)): ?>
      <div class="px-5 py-10 text-center"><p class="font-medium text-gray-700 dark:text-gray-200">Belum ada telemetry cronjob</p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aplikasi belum terhubung atau belum pernah mengirim log CRON.</p></div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left">
          <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400"><tr><th class="px-5 py-3 font-medium">Executed At</th><th class="px-5 py-3 font-medium">Tabel</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 text-right font-medium">Read</th><th class="px-5 py-3 text-right font-medium">Written</th><th class="px-5 py-3 font-medium">Durasi</th><th class="px-5 py-3 font-medium">Error</th></tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php foreach ($history as $log): ?>
              <?php $meta = $statusMeta($log['status']); ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]"><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($log['executed_at'])) ?></td><td class="px-5 py-4"><p class="font-medium text-gray-800 dark:text-white/90"><?= esc($log['table_name']) ?></p><code class="text-xs text-gray-400"><?= esc($log['table_code']) ?></code></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><?= esc($meta['label']) ?></span></td><td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_read']) ?></td><td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_written']) ?></td><td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300"><?= number_format((float) $log['duration_sec'], 2) ?>s</td><td class="max-w-xs px-5 py-4 text-sm <?= $log['error_message'] ? 'text-error-600 dark:text-error-400' : 'text-gray-400' ?>"><?= esc($log['error_message'] ?: '—') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
