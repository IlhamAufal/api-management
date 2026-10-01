<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$formatDate = static function ($value) {
    if (!$value) {
        return 'Never';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, H:i', $timestamp) : $value;
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-wide text-brand-500">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard Monitoring</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Live overview of SAP master-data synchronization tasks.</p>
    </div>
    <span class="text-sm text-gray-400"><?= count($tasks) ?> active tasks</span>
  </div>

  <?= view('components/pipeline_canvas', ['pipelineStatus' => $pipelineStatus]) ?>

  <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3 md:gap-6">
    <?php foreach ($metrics as $metric): ?>
      <?= view('components/stat_card', $metric) ?>
    <?php endforeach; ?>
  </section>

  <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
      <div>
        <h2 class="font-semibold text-gray-800 dark:text-white/90">Entity Synchronization</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest execution details for each registered task.</p>
      </div>
      <span class="text-xs text-gray-400">Manual sync logs are stored with trigger type MANUAL_UI.</span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-[1280px] w-full text-left">
        <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-400">
          <tr>
            <th class="px-5 py-3 font-medium">Entity Info</th>
            <th class="px-5 py-3 font-medium">Total Rows</th>
            <th class="px-5 py-3 font-medium">Last Synced At</th>
            <th class="px-5 py-3 font-medium">Duration</th>
            <th class="px-5 py-3 font-medium">Latest Status</th>
            <th class="px-5 py-3 font-medium">Cron Schedule</th>
            <th class="px-5 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php foreach ($tasks as $task): ?>
            <?php
            $log = $task['last_log'] ?? null;
            $status = $log['status'] ?? 'PENDING';
            $auditDetail = [
                'taskCode' => $task['task_code'],
                'taskName' => $task['task_name'],
                'stepFailed' => $log['step_failed'] ?? 'NONE',
                'recordsRead' => (int) ($log['records_read'] ?? 0),
                'recordsWritten' => (int) ($log['records_written'] ?? 0),
                'duration' => $log['duration_sec'] ?? '0.00',
                'errorMessage' => $log['error_message'] ?? null,
            ];
            $snippet = [
                'taskName' => $task['task_name'],
                'httpie' => 'http GET ' . $task['source_endpoint'],
                'sql' => "SELECT *\nFROM sys_sync_logs\nWHERE task_code = '" . $task['task_code'] . "'\nORDER BY executed_at DESC\nLIMIT 20;",
            ];
            ?>
            <tr
              id="sync-row-<?= esc($task['task_code'], 'attr') ?>"
              data-task-code="<?= esc($task['task_code'], 'attr') ?>"
              data-task-name="<?= esc($task['task_name'], 'attr') ?>"
              data-audit="<?= esc(json_encode($auditDetail), 'attr') ?>"
              class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($task['entity_label']) ?></p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?= esc($task['task_name']) ?></p>
                <p class="mt-1 font-mono text-[11px] text-gray-400"><?= esc($task['target_table']) ?></p>
              </td>
              <td class="px-5 py-4 text-sm font-medium text-gray-700 dark:text-gray-200"><?= number_format($task['total_rows']) ?></td>
              <td class="last-synced px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($formatDate($log['finished_at'] ?? $log['executed_at'] ?? null)) ?></td>
              <td class="duration px-5 py-4 text-sm text-gray-600 dark:text-gray-300"><?= esc($log ? number_format((float) $log['duration_sec'], 2) . 's' : '—') ?></td>
              <td class="status-cell px-5 py-4"><?= view('components/badge_status', ['status' => $status]) ?></td>
              <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['cron_expression'] ?: '—') ?></code></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <button type="button" class="sync-now inline-flex rounded-lg bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600 disabled:cursor-wait disabled:opacity-60">Sync Now</button>
                  <button type="button" class="audit-detail inline-flex rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Audit Detail</button>
                  <button type="button" class="copy-snippet inline-flex rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" data-snippet="<?= esc(json_encode($snippet), 'attr') ?>">Copy Snippet</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?= view('components/modal_sync_detail') ?>
<?= view('components/drawer_snippet') ?>

<script>
(() => {
  const notify = (type, message) => window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
  const badge = (status) => {
    const styles = {
      SUCCESS: 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
      RUNNING: 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
      FAILED: 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
      WARNING: 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
      PENDING: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'
    };
    const dot = { SUCCESS: 'bg-success-500', RUNNING: 'bg-warning-500 animate-pulse', FAILED: 'bg-error-500', WARNING: 'bg-warning-500', PENDING: 'bg-gray-400' };
    const normalized = (status || 'PENDING').toUpperCase();
    return `<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${styles[normalized] || styles.PENDING}"><span class="h-1.5 w-1.5 rounded-full ${dot[normalized] || dot.PENDING}"></span>${normalized}</span>`;
  };
  const formatTimestamp = (value) => value ? new Date(value.replace(' ', 'T')).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Never';
  const detailFromLog = (taskName, taskCode, log) => ({
    taskName, taskCode,
    stepFailed: log.step_failed || 'NONE', recordsRead: log.records_read || 0,
    recordsWritten: log.records_written || 0, duration: log.duration_sec || '0.00',
    errorMessage: log.error_message || null
  });

  document.querySelectorAll('.sync-now').forEach((button) => {
    button.addEventListener('click', async () => {
      const row = button.closest('tr');
      const taskCode = row.dataset.taskCode;
      button.disabled = true;
      button.textContent = 'Syncing…';
      try {
        const response = await fetch(`<?= base_url('api/sync/run') ?>/${encodeURIComponent(taskCode)}`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
          }
        });
        const payload = await response.json();
        if (!response.ok || !payload.log) throw new Error(payload.message || 'Sync request failed.');

        const log = payload.log;
        row.querySelector('.status-cell').innerHTML = badge(log.status);
        row.querySelector('.last-synced').textContent = formatTimestamp(log.finished_at || log.executed_at);
        row.querySelector('.duration').textContent = `${Number(log.duration_sec || 0).toFixed(2)}s`;
        row.dataset.audit = JSON.stringify(detailFromLog(row.dataset.taskName, taskCode, log));
        notify(log.status === 'SUCCESS' ? 'success' : 'error', payload.message);
      } catch (error) {
        notify('error', error.message || 'Unable to run sync.');
      } finally {
        button.disabled = false;
        button.textContent = 'Sync Now';
      }
    });
  });

  document.querySelectorAll('.audit-detail').forEach((button) => {
    button.addEventListener('click', () => {
      const row = button.closest('tr');
      window.dispatchEvent(new CustomEvent('open-audit', { detail: JSON.parse(row.dataset.audit || '{}') }));
    });
  });

  document.querySelectorAll('.copy-snippet').forEach((button) => {
    button.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('open-snippet', { detail: JSON.parse(button.dataset.snippet || '{}') }));
    });
  });
})();
</script>
<?= $this->endSection() ?>
