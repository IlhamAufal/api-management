<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <a href="<?= base_url('monitoring') ?>" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-brand-500 dark:text-gray-400">&larr; Kembali ke aplikasi</a>

  <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 sm:flex-row sm:items-start sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
    <div>
      <p class="font-mono text-xs font-medium uppercase tracking-wide text-brand-500"><?= esc($app['app_code']) ?></p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= esc($app['app_name']) ?></h1>
      <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400"><?= esc($app['description'] ?: 'Daftar tabel yang tersedia untuk aplikasi ini.') ?></p>
    </div>
    <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800">
      <p class="text-xs text-gray-400">Database</p>
      <p class="mt-1 font-mono font-semibold text-gray-700 dark:text-gray-200"><?= esc($app['database_name'] ?: '—') ?></p>
    </div>
  </div>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
      <h2 class="font-semibold text-gray-800 dark:text-white/90">Tabel Tersedia</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Endpoint API masih berupa konfigurasi holder dan akan digunakan saat integrasi API diaktifkan.</p>
    </div>

    <?php if (empty($tables)): ?>
      <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada tabel aktif untuk aplikasi ini.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left">
          <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-medium">Nama Tabel</th>
              <th class="px-5 py-3 font-medium">Kode</th>
              <th class="px-5 py-3 font-medium">API Resource</th>
              <th class="px-5 py-3 font-medium">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php foreach ($tables as $table): ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-4 font-semibold text-gray-800 dark:text-white/90"><?= esc($table['table_name']) ?></td>
                <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_code']) ?></code></td>
                <td class="px-5 py-4 font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($table['api_resource'] ?: '—') ?></td>
                <td class="px-5 py-4"><span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">API pending</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
