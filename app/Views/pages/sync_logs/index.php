<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Execution Logs'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Execution Logs</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Riwayat check freshness (append-only) dari <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">check_history</code>, urut terbaru dulu.</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
      <i class="fa-solid fa-table-cells" aria-hidden="true"></i>
      Buka Monitoring
    </a>
  </div>

  <!-- Section 1 — Filter & ringkasan (kartu terpisah dari tabel). -->
  <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Filter: status + source + trigger (GET; filter tidak valid diabaikan backend). -->
      <form method="get" class="flex flex-wrap items-center gap-3">
        <label for="status" class="text-sm font-medium text-gray-600 dark:text-gray-300">Filter</label>
        <select
          id="status"
          name="status"
          onchange="this.form.submit()"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-200"
        >
          <option value="" <?= $statusFilter === null ? 'selected' : '' ?>>Semua status</option>
          <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= esc($opt, 'attr') ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <select
          id="source"
          name="source"
          onchange="this.form.submit()"
          aria-label="Filter source"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-200"
        >
          <option value="" <?= $sourceFilter === null ? 'selected' : '' ?>>Semua source</option>
          <?php foreach ($sourceOptions as $opt): ?>
            <option value="<?= esc($opt['code'], 'attr') ?>" <?= $sourceFilter === $opt['code'] ? 'selected' : '' ?>><?= esc($opt['label']) ?></option>
          <?php endforeach; ?>
        </select>

        <select
          id="trigger"
          name="trigger"
          onchange="this.form.submit()"
          aria-label="Filter trigger"
          class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-200"
        >
          <option value="" <?= $triggerFilter === null ? 'selected' : '' ?>>Semua trigger</option>
          <?php foreach ($triggerOptions as $opt): ?>
            <option value="<?= esc($opt, 'attr') ?>" <?= $triggerFilter === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <?php if ($statusFilter !== null || $sourceFilter !== null || $triggerFilter !== null): ?>
          <a href="<?= base_url('logs') ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">Reset</a>
        <?php endif; ?>
      </form>

      <p class="text-sm text-gray-500 dark:text-gray-400">
        <?php if ($pagination['total'] > 0): ?>
          <?= number_format($pagination['total']) ?> baris
          <?php if ($statusFilter !== null || $sourceFilter !== null || $triggerFilter !== null): ?>
            (filter:
            <?php
            $chips = [];
            if ($statusFilter !== null) {
                $chips[] = 'status <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-800">' . esc($statusFilter) . '</code>';
            }
            if ($sourceFilter !== null) {
                $chips[] = 'source <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-800">' . esc($sourceFilter) . '</code>';
            }
            if ($triggerFilter !== null) {
                $chips[] = 'trigger <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-800">' . esc($triggerFilter) . '</code>';
            }
            echo implode(', ', $chips);
            ?>
            )
          <?php endif; ?>
          — halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?>
        <?php else: ?>
          Tidak ada baris
        <?php endif; ?>
      </p>
    </div>
  </section>

  <!-- Section 2 — Tabel + navigasi halaman. -->
  <section class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr>
            <th class="px-5 py-3 text-sm font-semibold">Dijalankan</th>
            <th class="px-5 py-3 text-sm font-semibold">Tabel</th>
            <th class="px-5 py-3 text-sm font-semibold">Source</th>
            <th class="px-5 py-3 text-sm font-semibold">Trigger</th>
            <th class="px-5 py-3 text-sm font-semibold">Status</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Baris</th>
            <th class="px-5 py-3 text-sm font-semibold">Sync Terakhir</th>
            <th class="px-5 py-3 text-sm font-semibold">Pesan Error</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="8" class="px-5 py-12 text-center">
                <i class="fa-solid fa-clock-rotate-left text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <?php $hasFilter = $statusFilter !== null || $sourceFilter !== null || $triggerFilter !== null; ?>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">
                  <?= $hasFilter ? 'Tidak ada log yang cocok dengan filter' : 'Belum ada log check' ?>
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  <?= $hasFilter ? 'Coba ganti atau reset filter.' : 'Tekan "Check All" di Monitoring untuk mulai mengisi riwayat.' ?>
                </p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"><?= esc($row['executed_at']) ?></td>
                <td class="px-5 py-3">
                  <?php if (! empty($row['table_label'])): ?>
                    <a href="<?= base_url('monitoring/history/' . (int) $row['watched_table_id'] . '/' . (int) $row['source_id']) ?>" class="font-semibold text-gray-800 hover:text-brand-500 dark:text-white/90"><?= esc($row['table_label']) ?></a>
                    <code class="block text-[10px] text-gray-400"><?= esc($row['table_name'] ?? '-') ?></code>
                  <?php else: ?>
                    <span class="text-xs text-gray-400">#<?= (int) $row['watched_table_id'] ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                  <span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($row['source_code'] ?? '#' . $row['source_id']) ?></span>
                </td>
                <td class="px-5 py-3"><span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($row['trigger_type']) ?></span></td>
                <td class="px-5 py-3"><?= view('components/badge_status', ['status' => $row['status']]) ?></td>
                <td class="px-5 py-3 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $row['row_count'] !== null ? number_format((int) $row['row_count']) : '—' ?></td>
                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"><?= $row['last_synced_at'] ?? '—' ?></td>
                <td class="max-w-[300px] px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                  <?php if (! empty($row['error_message'])): ?>
                    <span class="line-clamp-2" title="<?= esc($row['error_message'], 'attr') ?>"><?= esc($row['error_message']) ?></span>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pagination['totalPages'] > 1): ?>
      <?php
        $activeFilters = array_filter([
            'status'  => $statusFilter,
            'source'  => $sourceFilter,
            'trigger' => $triggerFilter,
        ]);
        $qs = static function (int $p) use ($activeFilters): string {
            return '?' . http_build_query($activeFilters + ['page' => $p]);
        };
        $base = base_url('logs');
        $cur  = $pagination['page'];
        $last = $pagination['totalPages'];
      ?>
      <nav class="flex items-center justify-between gap-3 border-t border-gray-100 p-4 dark:border-gray-800" aria-label="Navigasi halaman log">
        <?php if ($cur > 1): ?>
          <a href="<?= $base . $qs($cur - 1) ?>" rel="prev" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya
          </a>
        <?php else: ?>
          <span class="inline-flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm font-semibold text-gray-300 dark:border-gray-800 dark:text-gray-600">
            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya
          </span>
        <?php endif; ?>

        <span class="text-sm text-gray-500 dark:text-gray-400">Halaman <?= $cur ?> / <?= $last ?></span>

        <?php if ($cur < $last): ?>
          <a href="<?= $base . $qs($cur + 1) ?>" rel="next" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
            Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          </a>
        <?php else: ?>
          <span class="inline-flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm font-semibold text-gray-300 dark:border-gray-800 dark:text-gray-600">
            Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          </span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
