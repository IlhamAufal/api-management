<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$check   = $check ?? null;
$isCheck = $check !== null;
$statusMeta = static function ($status) use ($isCheck) {
    $map = [
        'SUCCESS'       => ['label' => 'Success', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
        'FAILED'        => ['label' => 'Failed', 'class' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'],
        'WARNING'       => ['label' => 'Warning', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'RUNNING'       => ['label' => 'Running', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'UNKNOWN'       => ['label' => $isCheck ? 'Belum Dicek' : 'No Cron Data', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
        'NOT_CONNECTED' => ['label' => 'Not Connected', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
    ];
    return $map[$status] ?? $map['UNKNOWN'];
};
$cmpBadge = static function ($status) {
    $map = [
        'MATCH'   => ['label' => 'Sejajar', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'],
        'GAP'     => ['label' => 'Selisih', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
        'ERROR'   => ['label' => 'Error', 'class' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'],
        'UNKNOWN' => ['label' => '—', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
    ];
    return $map[$status] ?? $map['UNKNOWN'];
};
$formatDate = static function ($value) {
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date('d M Y, H:i:s', $timestamp) : '—';
};
$fmtNum = static function ($value) {
    return $value === null ? '—' : number_format((int) $value);
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Monitoring', 'href' => base_url('monitoring')],
      ['label' => $app['app_name']],
  ]]) ?>

  <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 sm:flex-row sm:items-start sm:justify-between dark:border-gray-800 dark:bg-white/[0.03]">
    <div><p class="font-mono text-xs font-medium tracking-wide text-brand-500"><?= esc($app['app_code']) ?></p><h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= esc($app['app_name']) ?></h1><p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400"><?= esc($app['description'] ?: 'Status sinkronisasi dan riwayat cron aplikasi.') ?></p></div>
    <?php $appMeta = $statusMeta($summary['operational_status']); ?>
    <div class="text-right"><span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold <?= $appMeta['class'] ?>"><?= esc($appMeta['label']) ?></span><p class="mt-2 font-mono text-xs text-gray-400"><?= esc($app['database_name'] ?: '—') ?></p></div>
  </div>

  <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3 md:gap-6">
    <!-- Card 1 -->
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3C7.58 3 4 4.34 4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6c0-1.66-3.58-3-8-3Zm0 4.5c-3.93 0-6.5-1.13-6.5-1.5S8.07 4.5 12 4.5 18.5 5.63 18.5 6 15.93 7.5 12 7.5Zm0 8c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 12.61 9.36 13 12 13s5-.39 6.5-1.09V14c0 .37-2.57 1.5-6.5 1.5Zm0 4c-3.93 0-6.5-1.13-6.5-1.5v-2.09C7 16.61 9.36 17 12 17s5-.39 6.5-1.09V18c0 .37-2.57 1.5-6.5 1.5Z" fill="currentColor"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $isCheck ? 'Terpantau' : 'Telemetry Tersedia' ?></p>
        <p class="mt-0.5 text-xl font-bold text-gray-800 dark:text-white/90"><?= (int) $summary['reported_tables'] ?>/<?= count($tables) ?></p>
        <p class="mt-0.5 text-xs text-gray-400"><?= $isCheck ? 'Tabel memiliki log pemeriksaan' : 'Tabel memiliki log CRON' ?></p>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 3.5h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tabel Gagal</p>
        <p class="mt-0.5 text-xl font-bold <?= (int) $summary['failed_tables'] > 0 ? 'text-error-600 dark:text-error-400' : 'text-gray-800 dark:text-white/90' ?>"><?= (int) $summary['failed_tables'] ?></p>
        <p class="mt-0.5 text-xs text-gray-400"><?= $isCheck ? 'Berdasarkan pemeriksaan terakhir' : 'Berdasarkan cron terakhir' ?></p>
      </div>
    </div>

    <!-- Card 3 -->
    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $isCheck ? 'Pemeriksaan Terakhir' : 'Cron Terakhir' ?></p>
        <p class="mt-0.5 text-xl font-bold text-gray-800 dark:text-white/90"><?= esc($formatDate($summary['last_cron_at'])) ?></p>
        <p class="mt-0.5 text-xs text-gray-400"><?= $isCheck ? 'Aktivitas terbaru aplikasi' : 'Telemetry terbaru aplikasi' ?></p>
      </div>
    </div>
  </section>

  <div class="mt-6">
    <?= $this->include('components/workflow_canvas', ['workflow' => $workflow]) ?>
  </div>

  <?php if ($isCheck): ?>
    <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Perbandingan Sinkronisasi</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah baris sumber (yp_npd) vs penerima (yp_sap) &mdash; dibaca langsung, tanpa menyalin data.</p>
      </div>
      <button type="button" id="check-now" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36M21 4v4h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Check Now
      </button>
    </div>

    <?php if (!$check['configured']): ?>
      <div class="mt-3 flex items-start gap-3 rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
        <svg class="mt-0.5 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 3.5h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Kredensial belum diisi di <code class="rounded bg-warning-100 px-1.5 py-0.5 dark:bg-warning-500/20">.env</code> (<code>database.<?= esc($check['source']) ?>.username / .password</code>). Check Now akan gagal sampai credential diisi.</span>
      </div>
    <?php endif; ?>

    <section class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left">
          <thead class="bg-brand-500 text-white"><tr><th class="px-5 py-3 text-sm font-semibold">Tabel</th><th class="px-5 py-3 text-right text-sm font-semibold">Rows yp_npd</th><th class="px-5 py-3 text-right text-sm font-semibold">Rows yp_sap</th><th class="px-5 py-3 text-right text-sm font-semibold">Selisih</th><th class="px-5 py-3 text-sm font-semibold">Update yp_npd</th><th class="px-5 py-3 text-sm font-semibold">Update yp_sap</th><th class="px-5 py-3 text-sm font-semibold">Status</th></tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="comparison-body">
            <?php foreach ($check['comparison'] as $row): ?>
              <?php
              $cmeta = $cmpBadge($row['status']);
              $deltaText = $row['delta'] === null ? '—' : (($row['delta'] > 0 ? '+' : '') . number_format($row['delta']));
              $errTitle = trim((string) ($row['npd_error'] ?? '') . ' ' . (string) ($row['sap_error'] ?? ''));
              ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]" data-table-code="<?= esc($row['table_code'], 'attr') ?>">
                <td class="px-5 py-4"><p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($row['table_name']) ?></p><code class="mt-1 block text-xs text-gray-400"><?= esc($row['table_code']) ?></code></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200" data-cell="npd_rows"><?= $fmtNum($row['npd_rows']) ?></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200" data-cell="sap_rows"><?= $fmtNum($row['sap_rows']) ?></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-600 dark:text-gray-300" data-cell="delta"><?= esc($deltaText) ?></td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" data-cell="npd_updated"><?= esc($row['npd_updated'] ? $formatDate($row['npd_updated']) : '—') ?></td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" data-cell="sap_updated"><?= esc($row['sap_updated'] ? $formatDate($row['sap_updated']) : '—') ?></td>
                <td class="px-5 py-4" data-cell="status"<?= $errTitle !== '' ? ' title="' . esc($errTitle, 'attr') . '"' : '' ?>><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $cmeta['class'] ?>"><?= esc($cmeta['label']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Status Tabel</h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= $isCheck ? 'Status terkini berasal dari pemeriksaan read-only terakhir untuk setiap tabel.' : 'Status terkini berasal dari eksekusi CRON terakhir untuk setiap tabel.' ?></p>
  </div>

  <section class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1050px] text-left">
        <thead class="bg-brand-500 text-white"><tr><th class="px-5 py-3 text-sm font-semibold">Tabel</th><th class="px-5 py-3 text-sm font-semibold">Status</th><th class="px-5 py-3 text-sm font-semibold"><?= $isCheck ? 'Pemeriksaan Terakhir' : 'Cron Terakhir' ?></th><th class="px-5 py-3 text-right text-sm font-semibold"><?= $isCheck ? 'Rows' : 'Read' ?></th><?php if (!$isCheck): ?><th class="px-5 py-3 text-right text-sm font-semibold">Written</th><?php endif; ?><th class="px-5 py-3 text-sm font-semibold">Durasi</th><?php if (!$isCheck): ?><th class="px-5 py-3 text-sm font-semibold">Schedule</th><?php endif; ?></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php foreach ($tables as $table): ?>
            <?php $meta = $statusMeta($table['operational_status']); $lastCron = $table['last_cron']; ?>
            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]" data-task-code="<?= esc($table['sync_task_code'] ?? '', 'attr') ?>">
              <td class="px-5 py-4"><a href="<?= base_url('monitoring/' . rawurlencode($app['app_code']) . '/' . rawurlencode($table['table_code'])) ?>" class="inline-block font-semibold text-gray-800 transition hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400"><?= esc($table['table_name']) ?></a><code class="mt-1 block text-xs text-gray-400"><?= esc($table['table_code']) ?></code></td>
              <td class="px-5 py-4 status-cell"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><?= esc($meta['label']) ?></span></td>
              <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($lastCron['finished_at'] ?? $lastCron['executed_at'] ?? null)) ?></td>
              <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $lastCron ? number_format((int) $lastCron['records_read']) : '—' ?></td>
              <?php if (!$isCheck): ?><td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= $lastCron ? number_format((int) $lastCron['records_written']) : '—' ?></td><?php endif; ?>
              <td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300"><?= $lastCron ? number_format((float) $lastCron['duration_sec'], 2) . 's' : '—' ?></td>
              <?php if (!$isCheck): ?><td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($table['cron_expression'] ?: '—') ?></code></td><?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= $isCheck ? 'Riwayat Pemeriksaan' : 'Riwayat Cronjob' ?></h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= $isCheck ? 'Maksimal 50 pemeriksaan read-only terbaru. Log cron & manual digabung.' : 'Maksimal 50 eksekusi CRON terbaru. Trigger manual tidak dicampur ke riwayat ini.' ?></p>
  </div>

  <section class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <?php if (empty($history)): ?>
      <div class="px-5 py-10 text-center" id="history-empty"><p class="font-medium text-gray-700 dark:text-gray-200"><?= $isCheck ? 'Belum ada riwayat pemeriksaan' : 'Belum ada telemetry cronjob' ?></p><p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?= $isCheck ? 'Klik Check Now untuk menjalankan pemeriksaan read-only.' : 'Aplikasi belum terhubung atau belum pernah mengirim log CRON.' ?></p></div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[1050px] text-left">
          <thead class="bg-brand-500 text-white"><tr><th class="px-5 py-3 text-sm font-semibold">Executed At</th><th class="px-5 py-3 text-sm font-semibold">Tabel</th><th class="px-5 py-3 text-sm font-semibold">Status</th><th class="px-5 py-3 text-right text-sm font-semibold"><?= $isCheck ? 'Rows' : 'Read' ?></th><?php if (!$isCheck): ?><th class="px-5 py-3 text-right text-sm font-semibold">Written</th><?php endif; ?><th class="px-5 py-3 text-sm font-semibold">Durasi</th><th class="px-5 py-3 text-sm font-semibold">Error</th></tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="history-body">
            <?php foreach ($history as $log): ?>
              <?php $meta = $statusMeta($log['status']); ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]"><td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($log['executed_at'])) ?></td><td class="px-5 py-4"><p class="font-medium text-gray-800 dark:text-white/90"><?= esc($log['table_name']) ?></p><code class="text-xs text-gray-400"><?= esc($log['table_code']) ?></code></td><td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $meta['class'] ?>"><?= esc($meta['label']) ?></span></td><td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_read']) ?></td><?php if (!$isCheck): ?><td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $log['records_written']) ?></td><?php endif; ?><td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300"><?= number_format((float) $log['duration_sec'], 2) ?>s</td><td class="max-w-xs px-5 py-4 text-sm <?= $log['error_message'] ? 'text-error-600 dark:text-error-400' : 'text-gray-400' ?>"><?= esc($log['error_message'] ?: '—') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php if ($isCheck): ?>
<script>
(() => {
  const notify = (type, message) => window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));

  // Toast tersimpan sebelum reload (hasil Check Now).
  const saved = sessionStorage.getItem('mdbridge.check.toast');
  if (saved) {
    sessionStorage.removeItem('mdbridge.check.toast');
    try { const t = JSON.parse(saved); notify(t.type, t.message); } catch (e) {}
  }

  const btn = document.getElementById('check-now');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.textContent = 'Memeriksa…';
    try {
      const response = await fetch('<?= base_url('monitoring/check/' . rawurlencode($app['app_code'])) ?>', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        }
      });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'Pemeriksaan gagal.');

      const type = payload.status === 'SUCCESS' ? 'success' : (payload.status === 'WARNING' ? 'warning' : 'error');
      sessionStorage.setItem('mdbridge.check.toast', JSON.stringify({ type, message: payload.message }));
      location.reload();
    } catch (error) {
      notify('error', error.message || 'Pemeriksaan gagal.');
      btn.disabled = false;
      btn.innerHTML = original;
    }
  });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
