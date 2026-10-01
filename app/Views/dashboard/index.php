<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$formatDate = static function ($value) {
    if (!$value) {
        return 'Belum ada data';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, H:i', $timestamp) : $value;
};
$maxDailyRuns = 1;
foreach ($dailyStats as $day) {
    $maxDailyRuns = max($maxDailyRuns, (int) $day['total']);
}
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-wide text-brand-500">MD-Bridge Analytics</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard Integrasi</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan performa, tren eksekusi, dan anomali pipeline.</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Buka Monitoring</a>
  </div>

  <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm text-gray-500 dark:text-gray-400">Success Rate</p>
      <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white/90"><?= number_format($successRate, 1) ?>%</p>
      <p class="mt-2 text-xs text-gray-400"><?= number_format((int) $summary['successful_runs']) ?> dari <?= number_format((int) $summary['completed_runs']) ?> eksekusi selesai</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm text-gray-500 dark:text-gray-400">Records Written</p>
      <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white/90"><?= number_format((int) $summary['records_written']) ?></p>
      <p class="mt-2 text-xs text-gray-400">Akumulasi telemetry lokal</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm text-gray-500 dark:text-gray-400">Active Tasks</p>
      <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white/90"><?= number_format((int) $activeTasks) ?></p>
      <p class="mt-2 text-xs text-gray-400"><?= number_format((int) $activeApps) ?> aplikasi terdaftar</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm text-gray-500 dark:text-gray-400">Failed Runs</p>
      <p class="mt-2 text-3xl font-bold <?= (int) $summary['failed_runs'] > 0 ? 'text-error-600 dark:text-error-400' : 'text-gray-800 dark:text-white/90' ?>"><?= number_format((int) $summary['failed_runs']) ?></p>
      <p class="mt-2 text-xs text-gray-400">Durasi rata-rata <?= number_format((float) $summary['average_duration'], 2) ?> detik</p>
    </div>
  </section>

  <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 xl:col-span-2 dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-semibold text-gray-800 dark:text-white/90">Tren Eksekusi 7 Hari</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Success, warning, dan failed berdasarkan log eksekusi.</p>
        </div>
        <span class="text-xs text-gray-400">Total <?= number_format((int) $summary['total_runs']) ?> runs</span>
      </div>
      <div class="mt-6 grid grid-cols-7 items-end gap-2 sm:gap-4">
        <?php foreach ($dailyStats as $day): ?>
          <?php
          $successHeight = max(0, round(((int) $day['success'] / $maxDailyRuns) * 150));
          $warningHeight = max(0, round(((int) $day['warning'] / $maxDailyRuns) * 150));
          $failedHeight = max(0, round(((int) $day['failed'] / $maxDailyRuns) * 150));
          ?>
          <div class="flex flex-col items-center">
            <div class="flex h-40 w-full max-w-10 flex-col justify-end overflow-hidden rounded-t-lg bg-gray-100 dark:bg-gray-800" title="<?= esc($day['date'], 'attr') ?>: <?= (int) $day['total'] ?> runs">
              <?php if ($successHeight > 0): ?><div class="bg-success-500" style="height: <?= $successHeight ?>px"></div><?php endif; ?>
              <?php if ($warningHeight > 0): ?><div class="bg-warning-500" style="height: <?= $warningHeight ?>px"></div><?php endif; ?>
              <?php if ($failedHeight > 0): ?><div class="bg-error-500" style="height: <?= $failedHeight ?>px"></div><?php endif; ?>
            </div>
            <span class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?= esc($day['label']) ?></span>
            <span class="text-[10px] text-gray-400"><?= (int) $day['total'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-5 flex flex-wrap gap-4 border-t border-gray-100 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-success-500"></span>Success</span>
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-warning-500"></span>Warning</span>
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-error-500"></span>Failed</span>
      </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <h2 class="font-semibold text-gray-800 dark:text-white/90">Analisis Cepat</h2>
      <div class="mt-4 space-y-4">
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
          <p class="text-xs uppercase tracking-wide text-gray-400">Eksekusi terakhir</p>
          <p class="mt-1 font-semibold text-gray-800 dark:text-white/90"><?= esc($latestRun['task_code'] ?? 'Belum ada') ?></p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= esc($formatDate($latestRun['executed_at'] ?? null)) ?></p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
          <p class="text-xs uppercase tracking-wide text-gray-400">Running sekarang</p>
          <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= number_format((int) $summary['running_runs']) ?></p>
        </div>
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
          <p class="text-xs uppercase tracking-wide text-gray-400">Rekomendasi</p>
          <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300"><?= (int) $summary['failed_runs'] > 0 ? 'Periksa riwayat kegagalan pada halaman Monitoring.' : 'Tidak ada kegagalan tercatat. Pantau freshness cron secara berkala.' ?></p>
        </div>
      </div>
    </section>
  </div>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
      <h2 class="font-semibold text-gray-800 dark:text-white/90">Kegagalan Terbaru</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Anomali terbaru untuk ditindaklanjuti di Monitoring.</p>
    </div>
    <?php if (empty($recentFailures)): ?>
      <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada kegagalan yang tercatat.</div>
    <?php else: ?>
      <div class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php foreach ($recentFailures as $failure): ?>
          <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($failure['task_code']) ?></p><p class="mt-1 text-sm text-error-600 dark:text-error-400"><?= esc($failure['error_message'] ?: 'Tidak ada detail error.') ?></p></div>
            <span class="text-xs text-gray-400"><?= esc($formatDate($failure['executed_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
