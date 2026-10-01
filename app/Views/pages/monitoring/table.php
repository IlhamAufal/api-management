<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$checkMode = $checkMode ?? false;
$statusMeta = static function ($status) use ($checkMode) {
    $map = [
        'SUCCESS'       => ['label' => 'Success', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
        'FAILED'        => ['label' => 'Failed', 'class' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'],
        'WARNING'       => ['label' => 'Warning', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'RUNNING'       => ['label' => 'Running', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'UNKNOWN'       => ['label' => $checkMode ? 'Belum Dicek' : 'No Cron Data', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
        'NOT_CONNECTED' => ['label' => 'Not Connected', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
    ];
    return $map[$status] ?? $map['UNKNOWN'];
};
$formatDate = static function ($value) {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d M Y, H:i:s', $timestamp) : '—';
};
$formatDateShort = static function ($value) use ($checkMode) {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d M Y, H:i', $timestamp) : ($checkMode ? 'Belum dicek' : 'Belum ada cron');
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring', 'href' => base_url('monitoring')],
      ['label' => $app['app_name'], 'href' => base_url('monitoring/' . rawurlencode($app['app_code']))],
      ['label' => $table['table_name']],
  ]]) ?>

  <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 sm:flex-row sm:items-start sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
    <div>
      <!-- <p class="font-mono text-xs font-medium tracking-wide text-brand-500"><?= esc($table['table_code']) ?></p> -->
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= esc($table['table_name']) ?></h1>
      <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400"><?= $checkMode ? 'Detail pemeriksaan read-only dan riwayat untuk tabel ini pada aplikasi ' : 'Detail sinkronisasi, workflow, dan riwayat cronjob untuk tabel ini pada aplikasi ' ?><?= esc($app['app_name']) ?>.</p>
    </div>
    <?php $tableMeta = $statusMeta($table['operational_status']); ?>
    <div class="text-right">
      <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold <?= $tableMeta['class'] ?>"><?= esc($tableMeta['label']) ?></span>
      <p class="mt-2 font-mono text-xs text-gray-400"><?= esc($app['database_name'] ?: '—') ?></p>
    </div>
  </div>

  <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3 md:gap-6">
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3C7.58 3 4 4.34 4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6c0-1.66-3.58-3-8-3Zm0 4.5c-3.93 0-6.5-1.13-6.5-1.5S8.07 4.5 12 4.5 18.5 5.63 18.5 6 15.93 7.5 12 7.5Zm0 8c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 12.61 9.36 13 12 13s5-.39 6.5-1.09V14c0 .37-2.57 1.5-6.5 1.5Zm0 4c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 16.61 9.36 17 12 17s5-.39 6.5-1.09V18c0 .37-2.57 1.5-6.5 1.5Z" fill="currentColor"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rows Written</p>
        <p class="mt-0.5 text-xl font-bold text-gray-800 dark:text-white/90"><?= number_format((int) $totals['rows_written']) ?></p>
        <p class="mt-0.5 text-xs text-gray-400">Akumulasi <?= count($history) ?><?= $checkMode ? ' pemeriksaan terakhir' : ' eksekusi cron terakhir' ?></p>
      </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 3.5h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kegagalan</p>
        <p class="mt-0.5 text-xl font-bold <?= (int) $totals['failed_count'] > 0 ? 'text-error-600 dark:text-error-400' : 'text-gray-800 dark:text-white/90' ?>"><?= (int) $totals['failed_count'] ?></p>
        <p class="mt-0.5 text-xs text-gray-400"><?= (int) $totals['success_count'] ?> dari <?= count($history) ?><?= $checkMode ? ' pemeriksaan sukses' : ' cron sukses' ?></p>
      </div>
    </div>
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Durasi Rata-Rata</p>
        <p class="mt-0.5 text-xl font-bold text-gray-800 dark:text-white/90"><?= number_format((float) $totals['average_duration'], 2) ?>s</p>
        <p class="mt-0.5 text-xs text-gray-400"><?= $checkMode ? 'Pemeriksaan terakhir ' : 'Cron terakhir ' ?><?= esc($formatDateShort($lastCron['finished_at'] ?? $lastCron['executed_at'] ?? null)) ?></p>
      </div>
    </div>
  </section>

  <div class="mt-6">
    <?= $this->include('components/workflow_canvas', ['workflow' => $workflow]) ?>
  </div>

  <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Konfigurasi Task</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Registrasi task yang memetakan tabel ini ke sumber data.</p>
    </div>
    <?php if ($task !== null || !empty($table['sync_task_code'])): ?>
      <code class="inline-flex shrink-0 items-center rounded bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['sync_task_code']) ?></code>
    <?php endif; ?>
  </div>

  <section class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[720px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr><th class="px-5 py-3 text-sm font-semibold">Task Name</th><th class="px-5 py-3 text-sm font-semibold">Source Type</th><th class="px-5 py-3 text-sm font-semibold">Source Endpoint</th><th class="px-5 py-3 text-sm font-semibold">Target Table</th><th class="px-5 py-3 text-sm font-semibold">Batch Size</th><th class="px-5 py-3 text-sm font-semibold">Schedule</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if ($task !== null): ?>
            <tr>
              <td class="px-5 py-4 font-medium text-gray-800 dark:text-white/90"><?= esc($task['task_name']) ?></td>
              <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['source_type']) ?></span></td>
              <td class="max-w-xs px-5 py-4"><code class="block truncate rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300" title="<?= esc($task['source_endpoint'], 'attr') ?>"><?= esc($task['source_endpoint']) ?></code></td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['target_table']) ?></code></td>
              <td class="px-5 py-4 font-mono text-sm text-gray-700 dark:text-gray-200"><?= (int) $task['batch_size'] ?></td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['cron_expression'] ?: '—') ?></code></td>
            </tr>
          <?php elseif ($checkMode && !empty($table['sync_task_code'])): ?>
            <tr>
              <td class="px-5 py-4 font-medium text-gray-800 dark:text-white/90">Pemeriksaan read-only</td>
              <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">DB_READ</span></td>
              <td class="max-w-xs px-5 py-4"><code class="block truncate rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['api_resource'] ?? '—') ?></code></td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_code']) ?></code></td>
              <td class="px-5 py-4 font-mono text-sm text-gray-700 dark:text-gray-200">—</td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">—</code></td>
            </tr>
          <?php else: ?>
            <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-medium text-gray-700 dark:text-gray-200">Tabel belum terhubung ke task sinkronisasi</p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan sync_task_code pada tabel ini di registry monitoring_app_tables.</p></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= $checkMode ? 'Riwayat Pemeriksaan' : 'Riwayat Cronjob' ?></h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= $checkMode ? 'Maksimal 50 pemeriksaan read-only terakhir untuk tabel ini saja.' : 'Maksimal 50 eksekusi CRON terakhir untuk tabel ini saja.' ?></p>
  </div>

  <section class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1050px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr><th class="px-5 py-3 text-sm font-semibold">Executed At</th><th class="px-5 py-3 text-sm font-semibold">Status</th><th class="px-5 py-3 text-right text-sm font-semibold">Read</th><th class="px-5 py-3 text-right text-sm font-semibold">Written</th><th class="px-5 py-3 text-sm font-semibold">Durasi</th><th class="px-5 py-3 text-sm font-semibold">Error</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($history)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-medium text-gray-700 dark:text-gray-200"><?= $checkMode ? 'Belum ada riwayat pemeriksaan' : 'Belum ada telemetry cronjob' ?></p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= $checkMode ? 'Jalankan Check Now dari halaman aplikasi.' : 'Tabel ini belum pernah mengirim log CRON.' ?></p></td></tr>
          <?php else: ?>
            <?php foreach ($history as $log): ?>
              <?php $meta = $statusMeta($log['status']); ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($log['executed_at'])) ?></td>
                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><?= esc($meta['label']) ?></span></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_read']) ?></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_written']) ?></td>
                <td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300"><?= number_format((float) $log['duration_sec'], 2) ?>s</td>
                <td class="max-w-xs px-5 py-4 text-sm <?= $log['error_message'] ? 'text-error-600 dark:text-error-400' : 'text-gray-400' ?>"><?= esc($log['error_message'] ?: '—') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?= $this->endSection() ?>
