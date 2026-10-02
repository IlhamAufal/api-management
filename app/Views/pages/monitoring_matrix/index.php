<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Data Freshness Monitor</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Baris = tabel dipantau, kolom = source. Klik sel untuk melihat riwayat check.</p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-3">
      <a href="<?= base_url('monitoring/workflow') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:border-brand-700 dark:hover:text-brand-400">
        <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
        Alur Workflow
      </a>
      <form method="post" action="<?= base_url('monitoring/check-all') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
          <i class="fa-solid fa-rotate" aria-hidden="true"></i>
          Check All
        </button>
      </form>
    </div>
  </div>

  <section class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <?php if (empty($tables) || empty($sources)): ?>
        <div class="px-5 py-16 text-center">
          <i class="fa-solid fa-table-cells-large text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
          <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">
            <?= empty($tables) ? 'Belum ada watched table aktif' : 'Belum ada source aktif' ?>
          </p>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <?= empty($tables)
              ? 'Daftarkan tabel di halaman Watched Tables.'
              : 'Aktifkan minimal satu source di Config/Database.php + .env.' ?>
          </p>
        </div>
      <?php else: ?>
        <table class="w-full min-w-[720px] text-left">
          <thead class="bg-brand-500 text-white">
            <tr>
              <th class="sticky left-0 z-10 bg-brand-500 px-5 py-3 text-sm font-semibold">Tabel</th>
              <?php foreach ($sources as $source): ?>
                <th class="px-5 py-3 text-sm font-semibold">
                  <?= esc($source['label']) ?>
                  <code class="mt-0.5 block text-[10px] font-normal text-white/70"><?= esc($source['code']) ?></code>
                </th>
              <?php endforeach; ?>
              <th class="px-5 py-3 text-right text-sm font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php foreach ($tables as $table): ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="sticky left-0 z-10 bg-white px-5 py-4 dark:bg-gray-900">
                  <p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($table['label']) ?></p>
                  <code class="mt-0.5 block text-xs text-gray-400"><?= esc($table['table_name']) ?></code>
                  <code class="block text-[10px] text-gray-400">sync: <?= esc($table['sync_column']) ?> · stale &gt; <?= (int) $table['stale_after_minutes'] ?>m</code>
                </td>

                <?php foreach ($sources as $source): ?>
                  <?php
                    $key = $table['id'] . ':' . $source['id'];
                    $snapshot = $snapshots[$key] ?? null;
                    $status = $snapshot['status'] ?? 'NEVER_SYNCED';
                  ?>
                  <td class="px-5 py-4">
                    <a href="<?= base_url('monitoring/history/' . (int) $table['id'] . '/' . (int) $source['id']) ?>" class="group block rounded-lg p-2 -m-2 transition hover:bg-gray-50 dark:hover:bg-white/[0.04]" title="Lihat riwayat check">
                      <?= view('components/badge_status', ['status' => $status]) ?>
                      <span class="mt-1.5 block text-xs text-gray-500 dark:text-gray-400 group-hover:text-brand-500">
                        <?php if ($snapshot === null): ?>
                          belum pernah dicek
                        <?php else: ?>
                          sync <?= esc($relative($snapshot['last_synced_at'])) ?>
                          <?php if ($snapshot['row_count'] !== null): ?>
                            · <?= number_format((int) $snapshot['row_count']) ?> baris
                          <?php endif; ?>
                          <br /><span class="text-[10px] text-gray-400">dicek <?= esc($relative($snapshot['checked_at'])) ?></span>
                        <?php endif; ?>
                      </span>
                      <?php if (! empty($snapshot['error_message'])): ?>
                        <span class="mt-1 block max-w-[240px] truncate text-[10px] text-error-500" title="<?= esc($snapshot['error_message'], 'attr') ?>"><?= esc($snapshot['error_message']) ?></span>
                      <?php endif; ?>
                    </a>
                  </td>
                <?php endforeach; ?>

                <td class="px-5 py-4 text-right">
                  <form method="post" action="<?= base_url('monitoring/check-table/' . (int) $table['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" title="Check tabel ini di semua source" aria-label="Check <?= esc($table['label'], 'attr') ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                      <i class="fa-solid fa-rotate text-xs" aria-hidden="true"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>

  <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    Status dihitung dari umur kolom sync dibanding ambang stale per tabel — bukan perbandingan jumlah baris antar source. Trigger check manual; "STALE" berarti "terakhir dicek, datanya sudah lewat ambang".
  </p>
</div>
<?= $this->endSection() ?>
