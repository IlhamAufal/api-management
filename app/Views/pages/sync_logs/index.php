<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$appPayload = esc(json_encode($apps ?? [], JSON_HEX_APOS | JSON_HEX_AMP), 'attr');
$logPayload = esc(json_encode($logs ?? [], JSON_HEX_APOS | JSON_HEX_AMP), 'attr');
?>
<div
  x-data='executionLogs(<?= $appPayload ?>, <?= $logPayload ?>)'
>
  <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
    <?= view('components/breadcrumb', ['items' => [
        ['label' => 'Home', 'href' => base_url('/')],
        ['label' => 'Execution Logs'],
    ]]) ?>

    <section x-show="selectedApp === null" x-transition.opacity>
      <div class="mb-6 max-w-3xl">
        <p class="font-mono text-xs font-medium tracking-wide text-brand-500">MD-Bridge / Telemetry</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Execution Logs by Connected Application</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select an integration service to inspect pipeline telemetry and batch history.</p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 md:gap-6">
        <template x-for="app in apps" :key="app.id">
          <article class="group flex min-h-[300px] flex-col rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-700">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-base font-semibold text-gray-800 dark:text-white/90" x-text="app.name"></p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="app.service"></p>
              </div>
              <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="app.healthTone === 'warning' ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400' : 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'">
                <span class="h-1.5 w-1.5 rounded-full" :class="app.healthTone === 'warning' ? 'bg-warning-500' : 'bg-success-500'"></span>
                <span x-text="app.health"></span>
              </span>
            </div>
            <p class="mt-5 flex-1 text-sm leading-6 text-gray-500 dark:text-gray-400" x-text="app.description"></p>
            <div class="mt-5 rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
              <p class="text-xs text-gray-400" x-text="app.badgeDetail"></p>
              <p class="mt-1 font-mono text-xs font-medium text-gray-600 dark:text-gray-300" x-text="app.metrics"></p>
            </div>
            <button type="button" @click="selectApp(app)" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
              View Pipeline Logs <span aria-hidden="true">&rarr;</span>
            </button>
          </article>
        </template>
      </div>
    </section>

    <section x-show="selectedApp !== null" x-cloak x-transition.opacity>
      <button type="button" @click="backToApplications()" class="mb-6 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:text-brand-400">
        <span aria-hidden="true">&larr;</span> Back to Applications
      </button>

      <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Logs: <span class="font-mono" x-text="selectedApp?.name"></span></h1>
              <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="selectedApp?.healthTone === 'warning' ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400' : 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'">
                <span class="h-1.5 w-1.5 rounded-full" :class="selectedApp?.healthTone === 'warning' ? 'bg-warning-500' : 'bg-success-500'"></span><span x-text="selectedApp?.health"></span>
              </span>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-text="selectedApp?.badgeDetail"></p>
          </div>
          <div class="flex items-center gap-2 font-mono text-xs text-gray-400"><span class="h-2 w-2 rounded-full bg-success-500"></span> Live telemetry holder</div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <label class="block">
            <span class="mb-2 block text-xs font-medium text-gray-500 dark:text-gray-400">Select Master Entity Table</span>
            <select x-model="selectedTable" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
              <option value="all">All Master Tables (4)</option>
              <option value="sap_material_master">sap_material_master</option>
              <option value="sap_customer_master">sap_customer_master</option>
              <option value="sap_customer_material">sap_customer_material</option>
              <option value="sap_customer_sales_area">sap_customer_sales_area</option>
            </select>
          </label>
          <label class="block">
            <span class="mb-2 block text-xs font-medium text-gray-500 dark:text-gray-400">Search Error Messages</span>
            <input x-model.debounce.200ms="searchTerm" type="search" placeholder="Timeout, error code, payload…" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-gray-500" />
          </label>
          <label class="block">
            <span class="mb-2 block text-xs font-medium text-gray-500 dark:text-gray-400">Date Range</span>
            <select x-model="dateRange" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
              <option value="all">All available</option>
              <option value="today">Today</option>
              <option value="seven-days">Last 7 days</option>
            </select>
          </label>
        </div>
      </div>

      <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Batch Execution History</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><span x-text="visibleLogs().length"></span> matching records &middot; UTC+7 / WIB</p>
      </div>
      <div class="mt-3 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
          <table class="min-w-[1000px] w-full text-left">
            <thead class="bg-brand-500 text-white">
              <tr><th class="px-5 py-3 text-sm font-semibold">Executed At</th><th class="px-5 py-3 text-sm font-semibold">Target Entity Table</th><th class="px-5 py-3 text-sm font-semibold">Trigger Type</th><th class="px-5 py-3 text-right text-sm font-semibold">Records Processed</th><th class="px-5 py-3 text-sm font-semibold">Duration</th><th class="px-5 py-3 text-sm font-semibold">Status</th><th class="px-5 py-3 text-right text-sm font-semibold">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <template x-for="log in visibleLogs()" :key="log.id">
                <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                  <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-gray-600 dark:text-gray-300" x-text="log.executedAt"></td>
                  <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="log.table"></code></td>
                  <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 font-mono text-[11px] font-semibold" :class="log.trigger === 'CRON' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'" x-text="log.trigger"></span></td>
                  <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><span x-text="new Intl.NumberFormat('en-US').format(log.records)"></span> rows</td>
                  <td class="px-5 py-4 font-mono text-sm text-gray-600 dark:text-gray-300" x-text="log.duration"></td>
                  <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(log.status)"><span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(log.status)"></span><span x-show="log.status === 'FAILED'" aria-hidden="true">&#9888;</span><span x-text="log.status"></span></span></td>
                  <td class="px-5 py-4 text-right"><button type="button" @click="inspectLog(log)" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-brand-300 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:text-brand-400">Inspect Details</button></td>
                </tr>
              </template>
              <tr x-show="visibleLogs().length === 0"><td colspan="7" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">No log records match this table, search term, and date range.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <div x-show="inspectModal" x-cloak x-transition.opacity class="fixed inset-0 z-[100000] bg-gray-900/60 backdrop-blur-sm" @keydown.escape.window="inspectModal = false">
    <aside class="ml-auto flex h-full w-full max-w-xl flex-col border-l border-gray-200 bg-white shadow-theme-xl dark:border-gray-800 dark:bg-gray-900" @click.outside="inspectModal = false">
      <div class="flex items-start justify-between border-b border-gray-200 p-6 dark:border-gray-800">
        <div><p class="font-mono text-xs font-medium tracking-wide text-brand-500">Execution Inspection</p><h2 class="mt-2 text-xl font-semibold text-gray-800 dark:text-white/90" x-text="inspectedLog?.table || 'Pipeline log'"></h2><p class="mt-1 font-mono text-xs text-gray-400" x-text="inspectedLog?.executedAt"></p></div>
        <button type="button" @click="inspectModal = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200" aria-label="Close inspector">&#10005;</button>
      </div>
      <div class="flex-1 space-y-5 overflow-y-auto p-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pipeline Breakdown</p>
          <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800"><div><p class="text-sm font-semibold text-gray-800 dark:text-white/90">Step 1: Fetch Get</p><p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Retrieve source payload</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="inspectedLog?.stepFailed === 'FETCH_GET' ? 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'" x-text="inspectedLog?.stepFailed === 'FETCH_GET' ? 'Failed' : 'Complete'"></span></div>
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800"><div><p class="text-sm font-semibold text-gray-800 dark:text-white/90">Step 2: DB Insert</p><p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Persist processed batch</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="inspectedLog?.stepFailed === 'DB_INSERT' ? 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400' : 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'" x-text="inspectedLog?.stepFailed === 'DB_INSERT' ? 'Failed' : 'Complete'"></span></div>
          </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Error Code</p><p class="mt-2 font-mono text-sm text-error-600 dark:text-error-400" x-text="inspectedLog?.errorCode || 'No error code recorded' "></p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Raw Error Payload</p><pre class="mt-3 overflow-x-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-6 text-gray-100"><code x-text="inspectedLog?.errorPayload || 'No raw error payload available for this execution.'"></code></pre></div>
      </div>
    </aside>
  </div>
</div>

<script>
function executionLogs(apps, logs) {
  return {
    apps, logs,
    selectedApp: null,
    selectedTable: 'all',
    searchTerm: '',
    dateRange: 'all',
    inspectModal: false,
    inspectedLog: null,
    selectApp(app) { this.selectedApp = app; this.selectedTable = 'all'; this.searchTerm = ''; this.dateRange = 'all'; },
    backToApplications() { this.selectedApp = null; this.inspectModal = false; this.inspectedLog = null; },
    inspectLog(log) { this.inspectedLog = log; this.inspectModal = true; },
    visibleLogs() {
      if (!this.selectedApp) return [];
      const term = this.searchTerm.trim().toLowerCase();
      const now = new Date();
      const today = now.toISOString().slice(0, 10);
      const weekAgo = new Date(now); weekAgo.setDate(now.getDate() - 7);
      return this.logs.filter((log) => {
        if (log.app !== this.selectedApp.id) return false;
        if (this.selectedTable !== 'all' && log.table !== this.selectedTable) return false;
        if (term && ![log.errorCode, log.errorPayload, log.status, log.table].filter(Boolean).join(' ').toLowerCase().includes(term)) return false;
        if (this.dateRange === 'today' && !log.executedAt.startsWith(today)) return false;
        if (this.dateRange === 'seven-days' && new Date(log.executedAt.replace(' ', 'T')) < weekAgo) return false;
        return true;
      });
    },
    statusClass(status) {
      return {
        SUCCESS: 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
        RUNNING: 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
        FAILED: 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'
      }[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
    },
    statusDotClass(status) {
      return { SUCCESS: 'bg-success-500', RUNNING: 'bg-warning-500 animate-pulse', FAILED: 'bg-error-500' }[status] || 'bg-gray-400';
    }
  };
}
</script>
<?= $this->endSection() ?>
