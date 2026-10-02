<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-wide text-brand-500">MD-Bridge Analytics</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan performa integrasi.</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Buka Monitoring</a>
  </div>

  <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Source Aktif</p>
      <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= count($sources) ?></p>
      <p class="mt-1 text-xs text-gray-400"><?= esc(implode(', ', array_column($sources, 'code')) ?: '—') ?></p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Watched Tables</p>
      <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= count($tables) ?></p>
      <p class="mt-1 text-xs text-gray-400">tabel dipantau</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sel Pemantauan</p>
      <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= count($tables) * count($sources) ?></p>
      <p class="mt-1 text-xs text-gray-400">tabel × source</p>
    </div>
  </section>

  <?php
    // Warna segmen tren — konsisten dengan badge_status.php.
    $trendColors = [
      'OK'            => 'bg-success-500',
      'STALE'         => 'bg-warning-500',
      'NEVER_SYNCED'  => 'bg-gray-400',
      'MISSING_TABLE' => 'bg-error-400',
      'CONN_ERROR'    => 'bg-error-500',
    ];
    $maxTrend      = 0;
    $trendHasData  = false;
    foreach ($trend as $day) {
      $maxTrend     = max($maxTrend, (int) $day['total']);
      $trendHasData = $trendHasData || (int) $day['total'] > 0;
    }
    $snapshotTotal = array_sum($statusBreakdown);
  ?>

  <!-- Status sel terkini (table_snapshots) -->
  <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex items-baseline justify-between gap-4">
      <div>
        <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Status Sel Terkini</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Distribusi status dari <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800">table_snapshots</code> (seluruh pasangan tabel &times; source).</p>
      </div>
      <a href="<?= base_url('monitoring') ?>" class="shrink-0 text-sm font-semibold text-brand-500 hover:underline">Buka Monitoring</a>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
      <?php foreach ($statusBreakdown as $status => $count): ?>
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-white/[0.02]" data-status="<?= esc($status) ?>" data-count="<?= (int) $count ?>">
          <?= view('components/badge_status', ['status' => $status]) ?>
          <span class="text-xl font-bold text-gray-800 dark:text-white/90"><?= (int) $count ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($snapshotTotal === 0): ?>
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        Belum ada hasil check — jalankan <b>Check All</b> di halaman Monitoring untuk mengisi status.
      </p>
    <?php endif; ?>
  </section>

  <!-- Tren 7 hari + Health per source -->
  <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Tren Check (7 Hari)</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah eksekusi check per hari dari <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800">check_history</code>, ditumpuk per status.</p>

      <?php if (! $trendHasData): ?>
        <div class="mt-6 rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center dark:border-gray-700">
          <i class="fa-solid fa-chart-column text-xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
          <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada check dalam 7 hari terakhir.</p>
        </div>
      <?php else: ?>
        <div class="mt-6 flex h-48 gap-2">
          <?php foreach ($trend as $day): ?>
            <?php
              $breakdownParts = [];
              foreach (\App\Libraries\Monitoring\AnalyticsService::STATUS_ORDER as $st) {
                if ((int) ($day['byStatus'][$st] ?? 0) > 0) {
                  $breakdownParts[] = $st . ':' . (int) $day['byStatus'][$st];
                }
              }
              $barHeight = $maxTrend > 0 ? (int) round((int) $day['total'] / $maxTrend * 100) : 0;
            ?>
            <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1" data-trend-day="<?= esc($day['date']) ?>" data-total="<?= (int) $day['total'] ?>" data-breakdown="<?= esc(implode(',', $breakdownParts)) ?>">
              <div class="flex w-full flex-1 items-end justify-center">
                <?php if ((int) $day['total'] > 0): ?>
                  <div class="flex w-full max-w-[40px] flex-col-reverse overflow-hidden rounded-t-md" style="height: <?= $barHeight ?>%" role="img" aria-label="<?= (int) $day['total'] ?> check">
                    <?php foreach (\App\Libraries\Monitoring\AnalyticsService::STATUS_ORDER as $st): ?>
                      <?php $cnt = (int) ($day['byStatus'][$st] ?? 0); ?>
                      <?php if ($cnt > 0): ?>
                        <div class="<?= $trendColors[$st] ?? 'bg-gray-400' ?>" style="flex: <?= $cnt ?>" title="<?= esc($st) ?>: <?= $cnt ?>"></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="h-1 w-full max-w-[40px] rounded-sm bg-gray-200 dark:bg-gray-700"></div>
                <?php endif; ?>
              </div>
              <span class="truncate text-[10px] text-gray-400"><?= esc($day['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2">
          <?php foreach (\App\Libraries\Monitoring\AnalyticsService::STATUS_ORDER as $st): ?>
            <span class="inline-flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
              <span class="h-2.5 w-2.5 rounded-full <?= $trendColors[$st] ?? 'bg-gray-400' ?>"></span>
              <?= esc($st) ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
      <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Health per Source</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sel OK dibanding total sel dari snapshot terkini, per source aktif.</p>

      <?php if (empty($sourceHealth)): ?>
        <div class="mt-6 rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center dark:border-gray-700">
          <i class="fa-solid fa-server text-xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
          <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada source aktif.</p>
        </div>
      <?php else: ?>
        <ul class="mt-5 space-y-5">
          <?php foreach ($sourceHealth as $source): ?>
            <li data-source="<?= esc($source['code']) ?>" data-total="<?= (int) $source['total'] ?>" data-ok="<?= (int) $source['ok'] ?>">
              <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-gray-800 dark:text-white/90">
                  <?= esc($source['label']) ?>
                  <code class="ml-1.5 text-[10px] font-normal text-gray-400"><?= esc($source['code']) ?></code>
                </span>
                <span class="text-sm font-bold text-gray-700 dark:text-gray-200">
                  <?= (int) $source['ok'] ?>/<?= (int) $source['total'] ?> sel OK
                  <span class="ml-1 text-xs font-medium text-gray-400">(<?= (int) $source['percent'] ?>%)</span>
                </span>
              </div>
              <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-2 rounded-full <?= $source['percent'] >= 100 ? 'bg-success-500' : ($source['percent'] >= 50 ? 'bg-warning-500' : 'bg-error-500') ?>" style="width: <?= (int) $source['percent'] ?>%"></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($snapshotTotal === 0 && ! empty($sourceHealth)): ?>
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">Belum ada snapshot — angka akan terisi setelah Check All pertama.</p>
      <?php endif; ?>
    </div>
  </section>
</div>
<?= $this->endSection() ?>
