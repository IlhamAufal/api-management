<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400', 'dot' => 'bg-success-500'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'dot' => 'bg-gray-400'],
];
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Watched Tables'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Watched Tables</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Daftar tabel yang dipantau freshness-nya di semua source. Tambah tabel baru tanpa menyentuh kode.</p>
    </div>
    <a href="<?= base_url('watched-tables/new') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
      <i class="fa-solid fa-plus" aria-hidden="true"></i>
      Tambah Tabel
    </a>
  </div>

  <section class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[950px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr>
            <th class="px-5 py-3 text-sm font-semibold">Tabel</th>
            <th class="px-5 py-3 text-sm font-semibold">Label</th>
            <th class="px-5 py-3 text-sm font-semibold">Sync Column</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Stale After</th>
            <th class="px-5 py-3 text-sm font-semibold">Status</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($tables)): ?>
            <tr>
              <td colspan="6" class="px-5 py-12 text-center">
                <i class="fa-solid fa-table-list text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada watched table</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan tabel pertama dengan tombol "Tambah Tabel".</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tables as $table): ?>
              <?php $isActive = (int) $table['is_active'] === 1; ?>
              <?php $status = $statusMap[(int) $table['is_active']] ?? $statusMap[0]; ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_name']) ?></code></td>
                <td class="px-5 py-4 font-semibold text-gray-800 dark:text-white/90"><?= esc($table['label']) ?></td>
                <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['sync_column']) ?></code></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $table['stale_after_minutes']) ?> mnt</td>
                <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $status['class'] ?>"><span class="h-1.5 w-1.5 rounded-full <?= $status['dot'] ?>"></span><?= esc($status['label']) ?></span></td>
                <td class="px-5 py-4">
                  <div class="flex items-center justify-end gap-1.5">
                    <a href="<?= base_url('watched-tables/edit/' . (int) $table['id']) ?>" title="Edit" aria-label="Edit <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>
                    <form method="post" action="<?= base_url('watched-tables/toggle/' . (int) $table['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> tabel ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= base_url('watched-tables/delete/' . (int) $table['id']) ?>" onsubmit="return confirm('Nonaktifkan (soft delete) &quot;<?= esc($table['label'], 'attr') ?>&quot;? Riwayat check tetap tersimpan.')">
                      <?= csrf_field() ?>
                      <button type="submit" title="Hapus (soft)" aria-label="Hapus <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-error-600 transition hover:border-error-300 hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:border-error-700 dark:hover:bg-error-500/10">
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
