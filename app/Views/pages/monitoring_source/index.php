<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Data Freshness Monitor</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Pilih source untuk memantau umur data tiap tabel dan kepatuhan ambang batas stale</p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-3">
      <a href="<?= base_url('monitoring/workflow') ?>" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
        <i class="fa-solid fa-diagram-project text-xs" aria-hidden="true"></i>
        Alur Workflow
      </a>
    </div>
  </div>

  <?php if (empty($sources)): ?>
    <section class="mt-8 rounded-2xl border border-gray-200 bg-white px-5 py-16 text-center dark:border-gray-800 dark:bg-white/[0.03]">
      <i class="fa-solid fa-plug-circle-xmark text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
      <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada source aktif</p>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aktifkan minimal satu source di Config/Database.php + .env.</p>
    </section>
  <?php else: ?>
    <!-- Tab bar: satu tab per source aktif; tiap tab adalah URL nyata. -->
    <nav class="seg-tabs" aria-label="Pilih source">
      <?php foreach ($sources as $tab): ?>
        <?php $isActive = $activeSource !== null && (int) $tab['id'] === (int) $activeSource['id']; ?>
        <a
          href="<?= base_url('monitoring/source/' . $tab['code']) ?>"
          class="seg-tabs__item<?= $isActive ? ' is-active' : '' ?>"
          <?= $isActive ? 'aria-current="page"' : '' ?>
        >
          <?= esc($tab['label']) ?>
          <!-- <code class="seg-tabs__code"><?= esc($tab['code']) ?></code> -->
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($activeSource !== null): ?>
      <!-- Header source: ringkasan status + tombol Check Sumber Ini. -->
      <div class="mt-6 flex flex-col gap-4 rounded-xl border border-gray-200/80 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div class="flex flex-wrap items-center gap-2.5">
          <h2 class="mr-2 text-base font-semibold text-gray-900 dark:text-gray-100"><?= esc($activeSource['label']) ?></h2>
          <?php if (empty($summary)): ?>
            <span class="text-xs text-gray-500 dark:text-gray-400">Belum ada tabel dipantau</span>
          <?php else: ?>
            <?php foreach ($summary as $status => $count): ?>
              <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <?= view('components/badge_status', ['status' => $status]) ?>
                <span class="font-mono text-xs tabular-nums text-gray-700 dark:text-gray-300"><?= (int) $count ?></span>
              </span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= base_url('monitoring/check-source/' . $activeSource['code']) ?>" class="shrink-0">
          <?= csrf_field() ?>
          <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">
            <i class="fa-solid fa-rotate text-xs" aria-hidden="true"></i>
            Check Database
          </button>
        </form>
      </div>

      <section class="mt-4 overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
        <div class="overflow-x-auto">
          <?php if (empty($rows)): ?>
            <div class="px-5 py-16 text-center">
              <i class="fa-solid fa-table-cells-large text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
              <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada watched table aktif</p>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Daftarkan tabel di halaman Watched Tables.</p>
            </div>
          <?php else: ?>
            <table class="w-full min-w-[720px] text-left">
              <thead class="bg-brand-500 text-xs font-semibold uppercase tracking-wider text-white">
                <tr>
                  <th class="px-5 py-3">Tabel</th>
                  <th class="px-5 py-3">Status</th>
                  <th class="px-5 py-3">Umur Data</th>
                  <th class="px-5 py-3">Keterangan</th>
                  <th class="px-5 py-3 text-right">Baris</th>
                  <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
                <?php foreach ($rows as $row): ?>
                  <?php $table = $row['table']; $snapshot = $row['snapshot']; ?>
                  <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                    <td class="px-5 py-3.5">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?= esc($table['label']) ?></p>
                      <code class="mt-0.5 block font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc($table['table_name']) ?></code>
                      <code class="block font-mono text-[10px] text-gray-400">sync: <?= esc($table['sync_column']) ?> · stale &gt; <?= (int) $table['stale_after_minutes'] ?>m</code>
                    </td>
                    <td class="px-5 py-3.5">
                      <?= view('components/badge_status', ['status' => $row['status']]) ?>
                      <?php if (! empty($snapshot['error_message'])): ?>
                        <span class="mt-1 block max-w-[260px] truncate text-[10px] text-error-500" title="<?= esc($snapshot['error_message'], 'attr') ?>"><?= esc($snapshot['error_message']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                      <?php if ($snapshot === null): ?>
                        <span class="text-gray-400">belum dicek</span>
                      <?php else: ?>
                        <span>data terakhir: <?= esc($relative($snapshot['last_synced_at'])) ?></span>
                        <?php if ($snapshot['last_synced_at'] !== null): ?>
                          <span class="mt-0.5 block font-mono text-[10px] tabular-nums text-gray-400"><?= esc($snapshot['last_synced_at']) ?></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                      <?php if ($snapshot === null): ?>
                        <span class="text-gray-400">—</span>
                      <?php else: ?>
                        <span>dicek <?= esc($relative($snapshot['checked_at'])) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300">
                      <?= ($snapshot !== null && $snapshot['row_count'] !== null) ? number_format((int) $snapshot['row_count']) : '—' ?>
                    </td>
                    <td class="px-5 py-3.5">
                      <div class="flex items-center justify-end gap-1.5">
                        <a href="<?= base_url('monitoring/history/' . (int) $table['id'] . '/' . (int) $activeSource['id']) ?>" title="Lihat riwayat check" aria-label="Lihat riwayat check <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                          <i class="fa-solid fa-clock-rotate-left text-xs" aria-hidden="true"></i>
                        </a>
                        <form method="post" action="<?= base_url('monitoring/check-cell/' . (int) $table['id'] . '/' . (int) $activeSource['id']) ?>">
                          <?= csrf_field() ?>
                          <button type="submit" title="Check sel ini" aria-label="Check <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                            <i class="fa-solid fa-rotate text-xs" aria-hidden="true"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    Trigger check manual; "STALE" berarti "terakhir dicek, datanya sudah lewat ambang". Urut dari status terburuk lebih dulu.
  </p>
</div>
<?= $this->endSection() ?>
