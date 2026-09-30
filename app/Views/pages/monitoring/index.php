<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-brand-500">Data Explorer</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Monitoring Aplikasi</h1>
    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Pilih aplikasi untuk melihat tabel sumber yang tersedia. Data tabel akan diambil melalui API aplikasi terkait.</p>
  </div>

  <?php if (empty($apps)): ?>
    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-white/[0.03]">
      <h2 class="font-semibold text-gray-800 dark:text-white/90">Belum ada aplikasi aktif</h2>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Jalankan MonitoringAppsSeeder untuk mengisi registry aplikasi.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <?php foreach ($apps as $app): ?>
        <?php $initials = strtoupper(substr(str_replace(['-', '_'], '', $app['app_code']), 0, 2)); ?>
        <a href="<?= base_url('monitoring/' . rawurlencode($app['app_code'])) ?>" class="group flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-700">
          <div class="flex items-start justify-between gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-sm font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"><?= esc($initials) ?></span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400"><span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>Aktif</span>
          </div>

          <h2 class="mt-5 text-lg font-semibold text-gray-800 group-hover:text-brand-600 dark:text-white/90 dark:group-hover:text-brand-400"><?= esc($app['app_name']) ?></h2>
          <p class="mt-1 font-mono text-xs text-gray-400"><?= esc($app['app_code']) ?></p>
          <p class="mt-3 flex-1 text-sm leading-6 text-gray-500 dark:text-gray-400"><?= esc($app['description'] ?: 'Aplikasi sumber data untuk monitoring.') ?></p>

          <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500 dark:text-gray-400">Database</dt>
              <dd class="font-mono font-medium text-gray-700 dark:text-gray-200"><?= esc($app['database_name'] ?: '—') ?></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-500 dark:text-gray-400">Tabel tersedia</dt>
              <dd class="font-semibold text-gray-700 dark:text-gray-200"><?= number_format((int) $app['table_count']) ?></dd>
            </div>
          </dl>

          <span class="mt-5 inline-flex items-center text-sm font-semibold text-brand-500">Buka aplikasi <span class="ml-2 transition group-hover:translate-x-1">&rarr;</span></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
