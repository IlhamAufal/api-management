<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
];
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Watched Tables'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Watched Tables</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Daftar tabel yang dipantau freshness-nya di semua source tanpa menyentuh kode</p>
    </div>
    <a href="<?= base_url('watched-tables/new') ?>" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">
      <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
      Tambah Tabel
    </a>
  </div>

  <section class="mt-6 overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:mt-8">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[950px] text-left">
        <thead class="bg-brand-500 text-xs font-semibold uppercase tracking-wider text-white">
          <tr>
            <th class="px-5 py-3">Tabel</th>
            <th class="px-5 py-3">Source</th>
            <th class="px-5 py-3">Label</th>
            <th class="px-5 py-3">Sync Column</th>
            <th class="px-5 py-3 text-right">Stale After</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
          <?php if (empty($tables)): ?>
            <tr>
              <td colspan="7" class="px-5 py-12 text-center">
                <i class="fa-solid fa-table-list text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada watched table</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan tabel pertama dengan tombol "Tambah Tabel".</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tables as $table): ?>
              <?php $isActive = (int) $table['is_active'] === 1; ?>
              <?php $status = $statusMap[(int) $table['is_active']] ?? $statusMap[0]; ?>
              <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                <td class="px-5 py-3.5"><code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_name']) ?></code></td>
                <td class="px-5 py-3.5">
                  <?php if (! empty($table['source_code'])): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                      <i class="fa-solid fa-database text-[10px] text-gray-400" aria-hidden="true"></i>
                      <?= esc($table['source_label']) ?>
                      <span class="font-mono text-gray-500 dark:text-gray-400">(<?= esc($table['source_code']) ?>)</span>
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400" title="Tabel tidak ditemukan di source aktif manapun">
                      <i class="fa-solid fa-circle-question text-[10px]" aria-hidden="true"></i>
                      Tidak ditemukan
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 text-sm font-medium text-gray-900 dark:text-gray-100"><?= esc($table['label']) ?></td>
                <td class="px-5 py-3.5"><code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['sync_column']) ?></code></td>
                <td class="px-5 py-3.5 text-right font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300"><?= number_format((int) $table['stale_after_minutes']) ?> mnt</td>
                <td class="px-5 py-3.5"><span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $status['class'] ?>"><?= esc($status['label']) ?></span></td>
                <td class="px-5 py-4">
                  <div class="flex items-center justify-end gap-1.5">
                    <a href="<?= base_url('watched-tables/edit/' . (int) $table['id']) ?>" title="Edit" aria-label="Edit <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>
                    <form method="post" action="<?= base_url('watched-tables/toggle/' . (int) $table['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> tabel ini?')">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>" aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= base_url('watched-tables/delete/' . (int) $table['id']) ?>" onsubmit="return confirm('Nonaktifkan (soft delete) &quot;<?= esc($table['label'], 'attr') ?>&quot;? Riwayat check tetap tersimpan.')">
                      <?= csrf_field() ?>
                      <button type="submit" title="Hapus (soft)" aria-label="Hapus <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-error-600 transition-colors hover:border-error-300 hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:border-error-700 dark:hover:bg-error-500/10">
                        <i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pagination['total'] > 0): ?>
      <?php
        $cur  = $pagination['page'];
        $last = $pagination['totalPages'];
        $qs   = static function (int $p) use ($pagination): string {
            return '?' . http_build_query(['per_page' => $pagination['perPage'], 'page' => $p]);
        };
        $base = base_url('watched-tables');
        $from = ($cur - 1) * $pagination['perPage'] + 1;
        $to   = min($pagination['total'], $cur * $pagination['perPage']);
        $perPageChoices = [];
        foreach ($pagination['options'] as $opt) {
            $perPageChoices[(string) $opt] = $opt . ' / halaman';
        }
        $btn      = 'inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800';
        $btnDisabled = 'inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-100 px-3 text-xs font-medium text-gray-300 dark:border-gray-800 dark:text-gray-600';
      ?>
      <div class="flex flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div class="flex items-center gap-3">
          <form method="get" action="<?= $base ?>">
            <?= view('components/custom_select', ['cs' => [
                'name'       => 'per_page',
                'id'         => 'per_page',
                'value'      => (string) $pagination['perPage'],
                'options'    => $perPageChoices,
                'size'       => 'sm',
                'class'      => 'w-40 cs-select--up',
                'onchange'   => 'this.form.submit()',
                'attributes' => 'aria-label="Jumlah baris per halaman"',
                'searchable' => false,
            ]]) ?>
          </form>
          <p class="text-xs text-gray-500 tabular-nums dark:text-gray-400">Menampilkan <?= number_format($from) ?>–<?= number_format($to) ?> dari <?= number_format($pagination['total']) ?> baris</p>
        </div>

        <?php if ($last > 1): ?>
          <nav class="flex items-center gap-3" aria-label="Navigasi halaman watched tables">
            <?php if ($cur > 1): ?>
              <a href="<?= $base . $qs($cur - 1) ?>" rel="prev" class="<?= $btn ?>"><i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya</a>
            <?php else: ?>
              <span class="<?= $btnDisabled ?>"><i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya</span>
            <?php endif; ?>

            <span class="text-xs text-gray-500 tabular-nums dark:text-gray-400">Halaman <?= $cur ?> / <?= $last ?></span>

            <?php if ($cur < $last): ?>
              <a href="<?= $base . $qs($cur + 1) ?>" rel="next" class="<?= $btn ?>">Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i></a>
            <?php else: ?>
              <span class="<?= $btnDisabled ?>">Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i></span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
