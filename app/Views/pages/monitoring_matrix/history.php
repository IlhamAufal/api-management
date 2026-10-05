<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring', 'href' => base_url('monitoring')],
      ['label' => $table['label'] . ' × ' . $source['label']],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Riwayat Check</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['table_name']) ?></code>
        di source
        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($source['code']) ?></code>
        — urut terbaru dulu.
      </p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Kembali ke Monitoring
    </a>
  </div>

  <?php if ($snapshot !== null): ?>
    <section class="mt-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status Terkini</p>
        <div class="mt-2"><?= view('components/badge_status', ['status' => $snapshot['status']]) ?></div>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sync Terakhir</p>
        <p class="mt-2 font-semibold text-gray-800 dark:text-white/90"><?= esc($relative($snapshot['last_synced_at'])) ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400"><?= $snapshot['last_synced_at'] !== null ? esc($snapshot['last_synced_at']) : '—' ?></p>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Jumlah Baris</p>
        <p class="mt-2 font-semibold text-gray-800 dark:text-white/90"><?= $snapshot['row_count'] !== null ? number_format((int) $snapshot['row_count']) : '—' ?></p>
      </div>
      <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Terakhir Dicek</p>
        <p class="mt-2 font-semibold text-gray-800 dark:text-white/90"><?= esc($relative($snapshot['checked_at'])) ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400"><?= esc($snapshot['checked_at']) ?></p>
      </div>
    </section>

    <?php if (! empty($snapshot['error_message'])): ?>
      <div class="mt-4 flex items-start gap-2 rounded-xl border border-error-200 bg-error-50 p-4 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300">
        <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
        <span><?= esc($snapshot['error_message']) ?></span>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
      <form method="get" class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Filter status</span>
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
        <?php if ($statusFilter !== null): ?>
          <a href="<?= base_url('monitoring/history/' . $table['id'] . '/' . $source['id']) ?>" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">Reset</a>
        <?php endif; ?>
      </form>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        <?php if ($pagination['total'] > 0): ?>
          <?= number_format($pagination['total']) ?> baris
          <?php if ($statusFilter !== null): ?>(status <code class="rounded bg-gray-100 px-1 text-xs dark:bg-gray-800"><?= esc($statusFilter) ?></code>)<?php endif; ?>
          — halaman <?= $pagination['page'] ?> dari <?= $pagination['totalPages'] ?>
        <?php else: ?>
          Tidak ada baris
        <?php endif; ?>
      </p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full min-w-[820px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr>
            <th class="px-5 py-3 text-sm font-semibold">Dijalankan</th>
            <th class="px-5 py-3 text-sm font-semibold">Trigger</th>
            <th class="px-5 py-3 text-sm font-semibold">Status</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Baris</th>
            <th class="px-5 py-3 text-sm font-semibold">Sync Terakhir</th>
            <th class="px-5 py-3 text-sm font-semibold">Pesan Error</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($history)): ?>
            <tr>
              <td colspan="6" class="px-5 py-12 text-center">
                <i class="fa-solid fa-clock-rotate-left text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">
                  <?= $statusFilter !== null ? 'Tidak ada riwayat dengan status ini' : 'Belum ada riwayat check' ?>
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  <?= $statusFilter !== null ? 'Coba ganti atau reset filter status.' : 'Tekan tombol Check di halaman Monitoring untuk mengisi riwayat.' ?>
                </p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($history as $row): ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"><?= esc($row['executed_at']) ?></td>
                <td class="px-5 py-3"><span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($row['trigger_type']) ?></span></td>
                <td class="px-5 py-3"><?= view('components/badge_status', ['status' => $row['status']]) ?></td>
                <td class="px-5 py-3 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $row['row_count'] !== null ? number_format((int) $row['row_count']) : '—' ?></td>
                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-300"><?= $row['last_synced_at'] !== null ? esc($row['last_synced_at']) : '—' ?></td>
                <td class="max-w-[320px] px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
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
        $qs = static function (int $p) use ($statusFilter): string {
            $params = ['page' => $p];
            if ($statusFilter !== null) {
                $params['status'] = $statusFilter;
            }
            return '?' . http_build_query($params);
        };
        $base = base_url('monitoring/history/' . $table['id'] . '/' . $source['id']);
        $cur  = $pagination['page'];
        $last = $pagination['totalPages'];
      ?>
      <nav class="flex items-center justify-between gap-3 border-t border-gray-100 p-4 dark:border-gray-800" aria-label="Navigasi halaman riwayat">
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
