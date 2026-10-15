<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 border border-success-200/60 dark:bg-success-500/15 dark:text-success-400 dark:border-success-500/20', 'dot' => 'bg-success-500'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 border border-gray-200/60 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700/60', 'dot' => 'bg-gray-400'],
];
$configMap = [
    'pending' => ['class' => 'bg-warning-50 text-warning-700 border border-warning-200/60 dark:bg-warning-500/15 dark:text-warning-400 dark:border-warning-500/20', 'dot' => 'bg-warning-500'],
    'empty'   => ['class' => 'bg-warning-50 text-warning-700 border border-warning-200/60 dark:bg-warning-500/15 dark:text-warning-400 dark:border-warning-500/20', 'dot' => 'bg-warning-500'],
    'ok'      => ['class' => 'bg-success-50 text-success-700 border border-success-200/60 dark:bg-success-500/15 dark:text-success-400 dark:border-success-500/20', 'dot' => 'bg-success-500'],
];
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Databases'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Databases</h1>
      <p class="mt-1 max-w-[65ch] text-sm text-gray-500 dark:text-gray-400 leading-relaxed">Daftar database (source) yang dipantau. Kode di sini harus sama dengan nama connection group di Config/Database.php; credential diisi manual di .env.</p>
    </div>
    <a href="<?= base_url('databases/new') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition duration-150 ease-out hover:bg-brand-600 active:scale-[0.98] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
      <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
      Tambah Database
    </a>
  </div>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900/40">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[950px] text-left">
        <thead class="select-none bg-brand-500 text-white font-poppins">
          <tr>
            <th class="px-5 py-3.5 text-sm font-semibold">Kode</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Label</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Konfigurasi Credential</th>
            <th class="px-5 py-3.5 text-sm font-semibold">Status</th>
            <th class="px-5 py-3.5 text-right text-sm font-semibold">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
          <?php if (empty($sources)): ?>
            <tr>
              <td colspan="5" class="px-5 py-16 text-center select-none">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                  <i class="fa-solid fa-database text-lg" aria-hidden="true"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-200">Belum ada database</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftarkan database pertama dengan tombol &quot;Tambah Database&quot;.</p>
                <div class="mt-4">
                  <a href="<?= base_url('databases/new') ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750">
                    <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
                    Tambah Database
                  </a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sources as $source): ?>
              <?php
                $isActive = (int) $source['is_active'] === 1;
                $status   = $statusMap[(int) $source['is_active']] ?? $statusMap[0];
                $config   = $configMap[$source['config']['key']] ?? $configMap['pending'];
              ?>
              <tr class="transition-colors duration-150 hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-4">
                  <code class="inline-block select-text rounded-md border border-gray-200/70 bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700 dark:border-gray-700/60 dark:bg-gray-800 dark:text-gray-300"><?= esc($source['code']) ?></code>
                </td>
                <td class="px-5 py-4 font-semibold text-gray-800 dark:text-white/90">
                  <span class="inline-block select-text"><?= esc($source['label']) ?></span>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex select-none items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $config['class'] ?>" title="<?= esc($source['config']['hint'], 'attr') ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $config['dot'] ?>"></span><?= esc($source['config']['label']) ?>
                  </span>
                  <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 select-none">
                    <span class="inline-block select-text font-mono text-[11px] text-gray-400 dark:text-gray-500">database.<?= esc($source['code']) ?>.*</span>
                  </p>
                </td>
                <td class="px-5 py-4 select-none">
                  <span class="inline-flex select-none items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $status['class'] ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $status['dot'] ?>"></span><?= esc($status['label']) ?>
                  </span>
                </td>
                <td class="px-5 py-4 select-none">
                  <div class="flex select-none items-center justify-end gap-1.5">
                    <form method="post" action="<?= base_url('databases/test') ?>" title="Uji koneksi">
                      <?= csrf_field() ?>
                      <input type="hidden" name="code" value="<?= esc($source['code']) ?>" />
                      <input type="hidden" name="return_to" value="index" />
                      <button type="submit" title="Uji koneksi" aria-label="Uji koneksi <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition duration-150 ease-out hover:border-warning-300 hover:bg-warning-50 hover:text-warning-600 active:scale-95 dark:border-gray-700 dark:text-gray-400 dark:hover:border-warning-500/50 dark:hover:bg-warning-500/10 dark:hover:text-warning-400">
                        <i class="fa-solid fa-plug text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <a href="<?= base_url('databases/edit/' . (int) $source['id']) ?>" title="Edit" aria-label="Edit <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition duration-150 ease-out hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 active:scale-95 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>
                    <form method="post" action="<?= base_url('databases/toggle/' . (int) $source['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> database ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition duration-150 ease-out hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 active:scale-95 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= base_url('databases/delete/' . (int) $source['id']) ?>" onsubmit="return confirm('Nonaktifkan (soft delete) &quot;<?= esc($source['label'], 'attr') ?>&quot;? Riwayat check tetap tersimpan.')">
                      <?= csrf_field() ?>
                      <button type="submit" title="Hapus (soft)" aria-label="Hapus <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition duration-150 ease-out hover:border-error-300 hover:bg-error-50 hover:text-error-600 active:scale-95 dark:border-gray-700 dark:text-gray-400 dark:hover:border-error-700 dark:hover:bg-error-500/10">
                        <i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?= $this->endSection() ?>
