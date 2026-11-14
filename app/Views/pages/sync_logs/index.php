<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Execution Logs'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Execution Logs</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Riwayat check freshness dari <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">check_history</code>, urut terbaru dulu</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
      <i class="fa-solid fa-table-cells text-xs" aria-hidden="true"></i>
      Buka Monitoring
    </a>
  </div>

  <!-- Section 1 — Filter & ringkasan -->
  <section class="mt-6 rounded-xl border border-gray-200/80 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:mt-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <form method="get" class="flex flex-wrap items-center gap-2.5">
        <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Filter</span>

        <?= view('components/custom_select', ['cs' => [
            'name'        => 'status',
            'id'          => 'status',
            'value'       => $statusFilter ?? '',
            'options'     => $statusOptions,
            'placeholder' => 'Semua status',
            'size'        => 'sm',
            'class'       => 'w-44',
            'onchange'    => 'this.form.submit()',
            'attributes'  => 'aria-label="Filter status"',
        ]]) ?>

        <?php
        $sourceChoices = [];
        foreach ($sourceOptions as $opt) {
            $sourceChoices[$opt['code']] = $opt['label'];
        }
        ?>
        <?= view('components/custom_select', ['cs' => [
            'name'        => 'source',
            'id'          => 'source',
            'value'       => $sourceFilter ?? '',
            'options'     => $sourceChoices,
            'placeholder' => 'Semua source',
            'size'        => 'sm',
            'class'       => 'w-44',
            'onchange'    => 'this.form.submit()',
            'attributes'  => 'aria-label="Filter source"',
        ]]) ?>

        <?= view('components/custom_select', ['cs' => [
            'name'        => 'trigger',
            'id'          => 'trigger',
            'value'       => $triggerFilter ?? '',
            'options'     => $triggerOptions,
            'placeholder' => 'Semua trigger',
            'size'        => 'sm',
            'class'       => 'w-44',
            'onchange'    => 'this.form.submit()',
            'attributes'  => 'aria-label="Filter trigger"',
        ]]) ?>

        <?php if ($statusFilter !== null || $sourceFilter !== null || $triggerFilter !== null): ?>
          <a href="<?= base_url('logs') ?>" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">Reset</a>
        <?php endif; ?>
      </form>

      <p class="text-xs text-gray-500 tabular-nums dark:text-gray-400">
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
  <section class="mt-4 overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-left">
        <thead class="bg-brand-500 text-xs font-semibold uppercase tracking-wider text-white">
          <tr>
            <th class="px-5 py-3">Dijalankan</th>
            <th class="px-5 py-3">Tabel</th>
            <th class="px-5 py-3">Source</th>
            <th class="px-5 py-3">Trigger</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3 text-right">Baris</th>
            <th class="px-5 py-3">Sync Terakhir</th>
            <th class="px-5 py-3">Pesan Error</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80">
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
              <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                <td class="px-5 py-3 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300"><?= esc($row['executed_at']) ?></td>
                <td class="px-5 py-3">
                  <?php if (! empty($row['table_label'])): ?>
                    <a href="<?= base_url('monitoring/history/' . (int) $row['watched_table_id'] . '/' . (int) $row['source_id']) ?>" class="font-medium text-gray-900 hover:text-brand-500 dark:text-gray-100"><?= esc($row['table_label']) ?></a>
                    <code class="mt-0.5 block font-mono text-[10px] text-gray-400"><?= esc($row['table_name'] ?? '-') ?></code>
                  <?php else: ?>
                    <span class="font-mono text-xs text-gray-400">#<?= (int) $row['watched_table_id'] ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                  <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"><?= esc($row['source_code'] ?? '#' . $row['source_id']) ?></span>
                </td>
                <td class="px-5 py-3"><span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($row['trigger_type']) ?></span></td>
                <td class="px-5 py-3"><?= view('components/badge_status', ['status' => $row['status']]) ?></td>
                <td class="px-5 py-3 text-right font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300"><?= $row['row_count'] !== null ? number_format((int) $row['row_count']) : '—' ?></td>
                <td class="px-5 py-3 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-300"><?= $row['last_synced_at'] ?? '—' ?></td>
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
          <a href="<?= $base . $qs($cur - 1) ?>" rel="prev" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya
          </a>
        <?php else: ?>
          <span class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-100 px-3 text-xs font-medium text-gray-400 dark:border-gray-800 dark:text-gray-600">
            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i> Sebelumnya
          </span>
        <?php endif; ?>

        <span class="text-xs text-gray-500 tabular-nums dark:text-gray-400">Halaman <?= $cur ?> / <?= $last ?></span>

        <?php if ($cur < $last): ?>
          <a href="<?= $base . $qs($cur + 1) ?>" rel="next" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
            Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          </a>
        <?php else: ?>
          <span class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-100 px-3 text-xs font-medium text-gray-400 dark:border-gray-800 dark:text-gray-600">
            Berikutnya <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
          </span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection() ?>
