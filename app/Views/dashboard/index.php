<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Status Integrasi</h1>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ringkasan performa dan kondisi sinkronisasi</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">Buka Monitoring</a>
  </div>

  <section class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-6">
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Source Aktif</p>
      <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100"><?= count($sources) ?></p>
      <p class="mt-2 font-mono text-xs text-gray-500 dark:text-gray-400"><?= esc(implode(', ', array_column($sources, 'code')) ?: '—') ?></p>
    </div>
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Watched Tables</p>
      <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100"><?= count($tables) ?></p>
      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Tabel dipantau</p>
    </div>
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sel Pemantauan</p>
      <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100"><?= count($tables) * count($sources) ?></p>
      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Pasangan tabel × source</p>
    </div>
  </section>

  <?php
    // Warna segmen tren — konsisten dengan badge_status.php.
    $trendColors = [
      'OK'            => 'bg-success-500',
      'STALE'         => 'bg-warning-500',
      'NEVER_SYNCED'  => 'bg-gray-400 dark:bg-gray-600',
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
  <section class="mt-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60 sm:mt-8">
    <div>
      <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Status Sel Terkini</h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Distribusi status dari <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">table_snapshots</code> untuk seluruh pasangan tabel &times; source</p>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
      <?php foreach ($statusBreakdown as $status => $count): ?>
        <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-200/80 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/40" data-status="<?= esc($status) ?>" data-count="<?= (int) $count ?>">
          <?= view('components/badge_status', ['status' => $status]) ?>
          <span class="text-lg font-semibold tabular-nums text-gray-900 dark:text-gray-100"><?= (int) $count ?></span>
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
  <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 sm:mt-8">
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
      <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tren Pemeriksaan (7 Hari)</h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Jumlah eksekusi check per hari dari <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">check_history</code>, ditumpuk per status</p>

      <?php if (! $trendHasData): ?>
        <div class="mt-6 rounded-lg border border-dashed border-gray-200 p-8 text-center dark:border-gray-800">
          <i class="fa-solid fa-chart-column text-lg text-gray-400 dark:text-gray-500" aria-hidden="true"></i>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Belum ada check dalam 7 hari terakhir.</p>
        </div>
      <?php else: ?>
        <div class="mt-6 flex h-48 items-end gap-2 sm:gap-3">
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
            <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1.5" data-trend-day="<?= esc($day['date']) ?>" data-total="<?= (int) $day['total'] ?>" data-breakdown="<?= esc(implode(',', $breakdownParts)) ?>">
              <div class="flex w-full flex-1 items-end justify-center">
                <?php if ((int) $day['total'] > 0): ?>
                  <div class="flex w-full max-w-[36px] flex-col-reverse overflow-hidden rounded-t" style="height: <?= $barHeight ?>%" role="img" aria-label="<?= (int) $day['total'] ?> check">
                    <?php foreach (\App\Libraries\Monitoring\AnalyticsService::STATUS_ORDER as $st): ?>
                      <?php $cnt = (int) ($day['byStatus'][$st] ?? 0); ?>
                      <?php if ($cnt > 0): ?>
                        <div class="<?= $trendColors[$st] ?? 'bg-gray-400' ?>" style="flex: <?= $cnt ?>" title="<?= esc($st) ?>: <?= $cnt ?>"></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="h-1 w-full max-w-[36px] rounded-xs bg-gray-200 dark:bg-gray-700"></div>
                <?php endif; ?>
              </div>
              <span class="truncate font-mono text-[11px] tabular-nums text-gray-500 dark:text-gray-400"><?= esc($day['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
          <?php foreach (\App\Libraries\Monitoring\AnalyticsService::STATUS_ORDER as $st): ?>
            <?= view('components/badge_status', ['status' => $st]) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
      <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Health per Source</h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sel OK dibanding total sel dari snapshot terkini, per source aktif</p>

      <?php if (empty($sourceHealth)): ?>
        <div class="mt-6 rounded-lg border border-dashed border-gray-200 p-8 text-center dark:border-gray-800">
          <i class="fa-solid fa-server text-lg text-gray-400 dark:text-gray-500" aria-hidden="true"></i>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Belum ada source aktif.</p>
        </div>
      <?php else: ?>
        <ul class="mt-5 space-y-4">
          <?php foreach ($sourceHealth as $source): ?>
            <li data-source="<?= esc($source['code']) ?>" data-total="<?= (int) $source['total'] ?>" data-ok="<?= (int) $source['ok'] ?>">
              <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  <?= esc($source['label']) ?>
                  <code class="ml-1.5 rounded bg-gray-100 px-1 py-0.5 font-mono text-[11px] text-gray-500 dark:bg-gray-800 dark:text-gray-400"><?= esc($source['code']) ?></code>
                </span>
                <span class="text-sm font-medium tabular-nums text-gray-700 dark:text-gray-300">
                  <?= (int) $source['ok'] ?>/<?= (int) $source['total'] ?> sel OK
                  <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">(<?= (int) $source['percent'] ?>%)</span>
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
