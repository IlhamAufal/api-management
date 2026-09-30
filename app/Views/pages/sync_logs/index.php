<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$appPayload = esc(json_encode($apps ?? [], JSON_HEX_APOS | JSON_HEX_AMP), 'attr');
$logPayload = esc(json_encode($logs ?? [], JSON_HEX_APOS | JSON_HEX_AMP), 'attr');
?>
<div
  x-data='executionLogs(<?= $appPayload ?>, <?= $logPayload ?>)'
  class="min-h-[calc(100vh-73px)] bg-slate-950 text-slate-100"
>
  <div class="mx-auto max-w-[1440px] p-4 md:p-6 lg:p-8">
    <section x-show="selectedApp === null" x-transition.opacity>
      <div class="mb-8 max-w-3xl">
        <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-indigo-400">MD-Bridge / Telemetry</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white md:text-4xl">Execution Logs by Connected Application</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400 md:text-base">Select an integration service to inspect pipeline telemetry and batch history.</p>
      </div>

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <template x-for="app in apps" :key="app.id">
          <article class="group flex min-h-[300px] flex-col rounded-2xl border border-slate-700/60 bg-slate-800/80 p-6 shadow-2xl shadow-slate-950/20 transition duration-200 hover:-translate-y-1 hover:border-indigo-400/70 hover:bg-slate-800">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-base font-semibold text-white" x-text="app.name"></p>
                <p class="mt-1 text-sm text-slate-400" x-text="app.service"></p>
              </div>
              <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="app.healthTone === 'warning' ? 'bg-amber-400/10 text-amber-300 ring-1 ring-inset ring-amber-300/20' : 'bg-emerald-400/10 text-emerald-300 ring-1 ring-inset ring-emerald-300/20'">
                <span class="h-1.5 w-1.5 rounded-full" :class="app.healthTone === 'warning' ? 'bg-amber-400' : 'bg-emerald-400'"></span>
                <span x-text="app.health"></span>
              </span>
            </div>
            <p class="mt-5 text-sm leading-6 text-slate-300" x-text="app.description"></p>
            <div class="mt-5 rounded-xl border border-slate-700/60 bg-slate-900/60 px-4 py-3">
              <p class="text-xs text-slate-500" x-text="app.badgeDetail"></p>
              <p class="mt-1 font-mono text-xs font-medium text-slate-200" x-text="app.metrics"></p>
            </div>
            <button type="button" @click="selectApp(app)" class="mt-auto inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-800">
              View Pipeline Logs <span aria-hidden="true">→</span>
            </button>
          </article>
        </template>
      </div>
    </section>

    <section x-show="selectedApp !== null" x-cloak x-transition.opacity>
      <button type="button" @click="backToApplications()" class="mb-6 inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-700">
        <span aria-hidden="true">←</span> Back to Applications
      </button>

      <div class="rounded-2xl border border-slate-700/60 bg-slate-800/90 p-5 shadow-2xl shadow-slate-950/20 md:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-2xl font-semibold tracking-tight text-white">Logs: <span class="font-mono" x-text="selectedApp?.name"></span></h1>
              <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="selectedApp?.healthTone === 'warning' ? 'bg-amber-400/10 text-amber-300 ring-1 ring-inset ring-amber-300/20' : 'bg-emerald-400/10 text-emerald-300 ring-1 ring-inset ring-emerald-300/20'">
                <span class="h-1.5 w-1.5 rounded-full" :class="selectedApp?.healthTone === 'warning' ? 'bg-amber-400' : 'bg-emerald-400'"></span><span x-text="selectedApp?.health"></span>
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-400" x-text="selectedApp?.badgeDetail"></p>
          </div>
          <div class="flex items-center gap-2 font-mono text-xs text-slate-500"><span class="h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span> Live telemetry holder</div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 lg:grid-cols-[minmax(230px,0.9fr)_minmax(230px,1fr)_180px]">
          <label class="block">
            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-400">Select Master Entity Table</span>
            <select x-model="selectedTable" class="h-11 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 text-sm text-slate-100 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20">
              <option value="all">All Master Tables (4)</option>
              <option value="sap_material_master">sap_material_master</option>
              <option value="sap_customer_master">sap_customer_master</option>
              <option value="sap_customer_material">sap_customer_material</option>
              <option value="sap_customer_sales_area">sap_customer_sales_area</option>
            </select>
          </label>
          <label class="block">
            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-400">Search error messages</span>
            <input x-model.debounce.200ms="searchTerm" type="search" placeholder="Timeout, error code, payload…" class="h-11 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 text-sm text-slate-100 placeholder:text-slate-600 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20" />
          </label>
          <label class="block">
            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-400">Date range</span>
            <select x-model="dateRange" class="h-11 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 text-sm text-slate-100 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20">
              <option value="all">All available</option>
              <option value="today">Today</option>
              <option value="seven-days">Last 7 days</option>
            </select>
          </label>
        </div>
      </div>

      <div class="mt-5 overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-800/80 shadow-2xl shadow-slate-950/20">
        <div class="flex items-center justify-between border-b border-slate-700/60 px-5 py-4">
          <div><h2 class="font-semibold text-white">Batch execution history</h2><p class="mt-1 text-xs text-slate-500"><span x-text="visibleLogs().length"></span> matching records</p></div>
          <span class="font-mono text-xs text-slate-500">UTC+7 / WIB</span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[1000px] w-full text-left">
            <thead class="border-b border-slate-700/60 bg-slate-900/70 text-xs uppercase tracking-wider text-slate-500">
              <tr><th class="px-5 py-3 font-medium">Executed At</th><th class="px-5 py-3 font-medium">Target Entity Table</th><th class="px-5 py-3 font-medium">Trigger Type</th><th class="px-5 py-3 text-right font-medium">Records Processed</th><th class="px-5 py-3 font-medium">Duration</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 text-right font-medium">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <template x-for="log in visibleLogs()" :key="log.id">
                <tr class="transition hover:bg-slate-700/25">
                  <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-300" x-text="log.executedAt"></td>
                  <td class="px-5 py-4"><code class="rounded bg-slate-900 px-2 py-1 font-mono text-xs text-indigo-200" x-text="log.table"></code></td>
                  <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 font-mono text-[11px] font-semibold" :class="log.trigger === 'CRON' ? 'bg-violet-400/10 text-violet-300 ring-1 ring-inset ring-violet-300/20' : 'bg-sky-400/10 text-sky-300 ring-1 ring-inset ring-sky-300/20'" x-text="log.trigger"></span></td>
                  <td class="px-5 py-4 text-right font-mono text-sm text-slate-200"><span x-text="new Intl.NumberFormat('en-US').format(log.records)"></span> rows</td>
                  <td class="px-5 py-4 font-mono text-sm text-slate-300" x-text="log.duration"></td>
                  <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(log.status)"><span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(log.status)"></span><span x-show="log.status === 'FAILED'" aria-hidden="true">⚠</span><span x-text="log.status"></span></span></td>
                  <td class="px-5 py-4 text-right"><button type="button" @click="inspectLog(log)" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-indigo-400 hover:bg-indigo-400/10 hover:text-indigo-200">Inspect Details</button></td>
                </tr>
              </template>
              <tr x-show="visibleLogs().length === 0"><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">No log records match this table, search term, and date range.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <div x-show="inspectModal" x-cloak x-transition.opacity class="fixed inset-0 z-[100000] bg-slate-950/75 backdrop-blur-sm" @keydown.escape.window="inspectModal = false">
    <aside class="ml-auto flex h-full w-full max-w-xl flex-col border-l border-slate-700 bg-slate-900 shadow-2xl shadow-black/50" @click.outside="inspectModal = false">
      <div class="flex items-start justify-between border-b border-slate-700/60 p-6">
        <div><p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-indigo-400">Execution inspection</p><h2 class="mt-2 text-xl font-semibold text-white" x-text="inspectedLog?.table || 'Pipeline log'"></h2><p class="mt-1 font-mono text-xs text-slate-500" x-text="inspectedLog?.executedAt"></p></div>
        <button type="button" @click="inspectModal = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white" aria-label="Close inspector">✕</button>
      </div>
      <div class="flex-1 space-y-5 overflow-y-auto p-6">
        <div class="rounded-xl border border-slate-700/60 bg-slate-800/60 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pipeline breakdown</p>
          <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-lg border border-slate-700/60 bg-slate-900/70 p-3"><div><p class="text-sm font-semibold text-slate-200">Step 1: FETCH_GET</p><p class="mt-0.5 text-xs text-slate-500">Retrieve source payload</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="inspectedLog?.stepFailed === 'FETCH_GET' ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300'" x-text="inspectedLog?.stepFailed === 'FETCH_GET' ? 'FAILED' : 'COMPLETE'"></span></div>
            <div class="flex items-center justify-between rounded-lg border border-slate-700/60 bg-slate-900/70 p-3"><div><p class="text-sm font-semibold text-slate-200">Step 2: DB_INSERT</p><p class="mt-0.5 text-xs text-slate-500">Persist processed batch</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="inspectedLog?.stepFailed === 'DB_INSERT' ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300'" x-text="inspectedLog?.stepFailed === 'DB_INSERT' ? 'FAILED' : 'COMPLETE'"></span></div>
          </div>
        </div>
        <div class="rounded-xl border border-slate-700/60 bg-slate-800/60 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Error code</p><p class="mt-2 font-mono text-sm text-rose-300" x-text="inspectedLog?.errorCode || 'No error code recorded' "></p></div>
        <div class="rounded-xl border border-slate-700/60 bg-slate-800/60 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Raw error payload</p><pre class="mt-3 overflow-x-auto rounded-lg bg-slate-950 p-4 font-mono text-xs leading-6 text-slate-300"><code x-text="inspectedLog?.errorPayload || 'No raw error payload available for this execution.'"></code></pre></div>
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
      return { SUCCESS: 'bg-emerald-400/10 text-emerald-300 ring-1 ring-inset ring-emerald-300/20', RUNNING: 'bg-blue-400/10 text-blue-300 ring-1 ring-inset ring-blue-300/20', FAILED: 'bg-rose-400/10 text-rose-300 ring-1 ring-inset ring-rose-300/20' }[status] || 'bg-slate-700 text-slate-300';
    },
    statusDotClass(status) {
      return { SUCCESS: 'bg-emerald-400', RUNNING: 'bg-blue-400 animate-pulse', FAILED: 'bg-rose-400' }[status] || 'bg-slate-400';
    }
  };
}
</script>
<?= $this->endSection() ?>
