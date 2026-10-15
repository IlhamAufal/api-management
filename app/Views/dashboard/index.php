<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
  // Kartu statistik atas — tiap kartu punya warna aksen sendiri (lihat
  // `.dash-card--*` di assets/css/utilities-patch.css).
  $statCards = [
    [
      'tone'  => 'brand',
      'icon'  => 'fa-solid fa-plug',
      'label' => 'Source Aktif',
      'value' => count($sources),
      'meta'  => implode(' · ', array_column($sources, 'code')) ?: '—',
    ],
    [
      'tone'  => 'orange',
      'icon'  => 'fa-solid fa-table',
      'label' => 'Watched Tables',
      'value' => count($tables),
      'meta'  => 'tabel dipantau',
    ],
    [
      'tone'  => 'sky',
      'icon'  => 'fa-solid fa-table-cells-large',
      'label' => 'Sel Pemantauan',
      'value' => count($tables) * count($sources),
      'meta'  => 'tabel × source',
    ],
  ];

  // Keterangan tiap status sel — dipakai di kolom kanan baris "Status Sel Terkini".
  $statusMeta = [
    'OK'             => ['tone' => 'ok', 'desc' => 'Sel sehat: tabel ditemukan dan umur data terbaru masih di bawah batas segar (stale_after_minutes).'],
    'STALE'          => ['tone' => 'stale', 'desc' => 'Data basi: sync terakhir sudah melewati batas segar, perlu di-refresh.'],
    'NEVER_SYNCED'   => ['tone' => 'never_synced', 'desc' => 'Belum pernah berhasil sync sejak sel ini didaftarkan.'],
    'MISSING_TABLE'  => ['tone' => 'missing_table', 'desc' => 'Tabel tidak ditemukan di source terkait (nama salah atau belum dibuat).'],
    'PENDING_CONFIG' => ['tone' => 'pending_config', 'desc' => 'Connection group source belum terdaftar di Config/Database.php — credential perlu diisi.'],
    'CONN_ERROR'     => ['tone' => 'conn_error', 'desc' => 'Koneksi atau query ke source gagal saat pengecekan terakhir.'],
  ];

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
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="font-poppins mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan performa integrasi.</p>
    </div>
    <a href="<?= base_url('monitoring') ?>" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Buka Monitoring</a>
  </div>

  <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <?php foreach ($statCards as $card): ?>
      <div class="dash-card dash-card--<?= $card['tone'] ?>">
        <div class="dash-card__head">
          <span class="dash-card__icon"><i class="<?= $card['icon'] ?>" aria-hidden="true"></i></span>
          <span class="dash-pill"><?= esc($card['label']) ?></span>
        </div>
        <p class="dash-card__value"><?= (int) $card['value'] ?></p>
        <p class="dash-card__meta"><?= esc($card['meta']) ?></p>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Status sel terkini (table_snapshots) -->
  <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <span class="dash-hicon dash-hicon--brand"><i class="fa-solid fa-signal" aria-hidden="true"></i></span>
        <div>
          <h2 class="font-poppins text-base font-semibold text-gray-800 dark:text-white/90">Status Sel Terkini</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Distribusi status dari <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800">table_snapshots</code> (seluruh pasangan tabel &times; source).</p>
        </div>
      </div>
      <a href="<?= base_url('monitoring') ?>" class="shrink-0 text-sm font-semibold text-brand-500 hover:underline">Buka Monitoring</a>
    </div>

    <div class="dash-status">
      <div class="dash-status__head">
        <span>Status</span>
        <span>Sel</span>
        <span>Keterangan</span>
      </div>
      <?php foreach ($statusBreakdown as $status => $count): ?>
        <?php
          $meta = $statusMeta[$status] ?? ['tone' => 'pending_config', 'desc' => 'Status belum terdefinisi.'];
        ?>
        <div class="dash-status__row dash-tone-<?= $meta['tone'] ?>" data-status="<?= esc($status) ?>" data-count="<?= (int) $count ?>">
          <span class="dash-chip font-poppins"><?= esc($status) ?></span>
          <span class="dash-status__count font-poppins"><?= (int) $count ?> <small>sel</small></span>
          <span class="dash-status__desc"><?= esc($meta['desc']) ?></span>
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
      <div class="flex items-start gap-3">
        <span class="dash-hicon dash-hicon--orange"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
        <div>
          <h2 class="font-poppins text-base font-semibold text-gray-800 dark:text-white/90">Tren Check (7 Hari)</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah eksekusi check per hari dari <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800">check_history</code>, ditumpuk per status.</p>
        </div>
      </div>

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
              <span class="truncate font-poppins text-[10px] text-gray-400"><?= esc($day['label']) ?></span>
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
      <div class="flex items-start gap-3">
        <span class="dash-hicon dash-hicon--success"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i></span>
        <div>
          <h2 class="font-poppins text-base font-semibold text-gray-800 dark:text-white/90">Health per Source</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sel OK dibanding total sel dari snapshot terkini, per source aktif.</p>
        </div>
      </div>

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
                <span class="font-poppins text-sm font-bold text-gray-700 dark:text-gray-200">
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
