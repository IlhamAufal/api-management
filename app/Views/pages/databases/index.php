<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
];
$configMap = [
    'pending' => ['class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
    'empty'   => ['class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
    'ok'      => ['class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
];
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Databases'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Databases</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Daftar database (source) yang dipantau; credential dikonfigurasi melalui .env</p>
    </div>
    <a href="<?= base_url('databases/new') ?>" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">
      <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
      Tambah Database
    </a>
  </div>

  <section class="mt-6 overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:mt-8">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[950px] text-left">
        <thead class="bg-brand-500 text-xs font-semibold uppercase tracking-wider text-white">
          <tr>
            <th class="px-5 py-3">Kode</th>
            <th class="px-5 py-3">Label</th>
            <th class="px-5 py-3">Konfigurasi Credential</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
          <?php if (empty($sources)): ?>
            <tr>
              <td colspan="5" class="px-5 py-12 text-center">
                <i class="fa-solid fa-database text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada database</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan database pertama dengan tombol "Tambah Database".</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($sources as $source): ?>
              <?php
                $isActive = (int) $source['is_active'] === 1;
                $status   = $statusMap[(int) $source['is_active']] ?? $statusMap[0];
                $config   = $configMap[$source['config']['key']] ?? $configMap['pending'];
              ?>
              <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                <td class="px-5 py-3.5"><code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"><?= esc($source['code']) ?></code></td>
                <td class="px-5 py-3.5 text-sm font-medium text-gray-900 dark:text-gray-100"><?= esc($source['label']) ?></td>
                <td class="px-5 py-3.5">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $config['class'] ?>" title="<?= esc($source['config']['hint'], 'attr') ?>">
                    <?= esc($source['config']['label']) ?>
                  </span>
                  <p class="mt-1 font-mono text-[11px] text-gray-400 dark:text-gray-500">database.<?= esc($source['code']) ?>.*</p>
                </td>
                <td class="px-5 py-3.5"><span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $status['class'] ?>"><?= esc($status['label']) ?></span></td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center justify-end gap-1.5">
                    <form method="post" action="<?= base_url('databases/test') ?>" title="Uji koneksi">
                      <?= csrf_field() ?>
                      <input type="hidden" name="code" value="<?= esc($source['code']) ?>" />
                      <input type="hidden" name="return_to" value="index" />
                      <button type="submit" title="Uji koneksi" aria-label="Uji koneksi <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                        <i class="fa-solid fa-plug text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <a href="<?= base_url('databases/edit/' . (int) $source['id']) ?>" title="Edit" aria-label="Edit <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>
                    <form method="post" action="<?= base_url('databases/toggle/' . (int) $source['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> database ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= base_url('databases/delete/' . (int) $source['id']) ?>" onsubmit="return confirm('Nonaktifkan (soft delete) &quot;<?= esc($source['label'], 'attr') ?>&quot;? Riwayat check tetap tersimpan.')">
                      <?= csrf_field() ?>
                      <button type="submit" title="Hapus (soft)" aria-label="Hapus <?= esc($source['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-error-600 transition-colors hover:border-error-300 hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:border-error-700 dark:hover:bg-error-500/10">
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
