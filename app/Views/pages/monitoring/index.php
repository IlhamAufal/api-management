<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMeta = static function ($status) {
    $map = [
        'SUCCESS'       => ['label' => 'Healthy', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400', 'dot' => 'bg-success-500'],
        'FAILED'        => ['label' => 'Failed', 'class' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400', 'dot' => 'bg-error-500'],
        'RUNNING'       => ['label' => 'Running', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400', 'dot' => 'bg-warning-500 animate-pulse'],
        'UNKNOWN'       => ['label' => 'Belum Ada Log', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'dot' => 'bg-gray-400'],
        'NOT_CONNECTED' => ['label' => 'Not Connected', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'dot' => 'bg-gray-400'],
    ];
    return $map[$status] ?? $map['UNKNOWN'];
};
$formatDate = static function ($value) {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d M Y, H:i', $timestamp) : 'Belum ada log';
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-wide text-brand-500">Operational Monitoring</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Monitoring Sinkronisasi</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Pilih aplikasi untuk membuka detail status tabel dan riwayat sinkronisasi.</p>
    </div>
  </div>

  <?php if (empty($apps)): ?>
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-white/[0.03]">
      <h2 class="font-semibold text-gray-800 dark:text-white/90">Belum ada aplikasi aktif</h2>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jalankan MonitoringAppsSeeder untuk mengisi registry aplikasi.</p>
    </div>
  <?php else: ?>
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6" aria-label="Daftar aplikasi monitoring">
      <?php foreach ($apps as $app): ?>
        <?php
        $initials = strtoupper(substr(str_replace(['-', '_'], '', $app['app_code']), 0, 2));
        $meta = $statusMeta($app['operational_status']);
        ?>
        <a
          href="<?= base_url('monitoring/' . rawurlencode($app['app_code'])) ?>"
          class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-700"
        >
          <div class="flex items-start justify-between gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-sm font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><?= esc($initials) ?></span>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><span class="h-1.5 w-1.5 rounded-full <?= $meta['dot'] ?>"></span><?= esc($meta['label']) ?></span>
          </div>
          <h2 class="mt-5 text-lg font-semibold text-gray-800 group-hover:text-brand-600 dark:text-white/90 dark:group-hover:text-brand-400"><?= esc($app['app_name']) ?></h2>
          <p class="mt-1 font-mono text-xs text-gray-400"><?= esc($app['app_code']) ?></p>
          <p class="mt-3 flex-1 text-sm leading-6 text-gray-500 dark:text-gray-400"><?= esc($app['description'] ?: 'Aplikasi sumber data untuk monitoring.') ?></p>
          <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
            <div class="flex items-center justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Database</dt><dd class="font-mono font-medium text-gray-700 dark:text-gray-200"><?= esc($app['database_name'] ?: '—') ?></dd></div>
            <div class="flex items-center justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Telemetry Tabel</dt><dd class="font-semibold text-gray-700 dark:text-gray-200"><?= (int) $app['reported_tables'] ?>/<?= (int) $app['table_count'] ?></dd></div>
            <div class="flex items-center justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400"><?= !empty($app['check_supported']) ? 'Pemeriksaan Terakhir' : 'Cron Terakhir' ?></dt><dd class="text-xs font-medium text-gray-700 dark:text-gray-200"><?= esc($formatDate($app['last_cron_at'])) ?></dd></div>
          </dl>
          <span class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition group-hover:bg-brand-600">
            Lihat Status &amp; Riwayat
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m0 0-6-6m6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
