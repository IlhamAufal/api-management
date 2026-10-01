<div
  x-data="{ open: false, detail: {} }"
  x-on:open-audit.window="detail = $event.detail || {}; open = true"
  x-show="open"
  x-transition.opacity
  class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/60 p-4"
  style="display: none;"
  @keydown.escape.window="open = false"
>
  <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900" @click.outside="open = false">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-xs font-medium tracking-wide text-brand-500">Execution Audit</p>
        <h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90" x-text="detail.taskName || detail.taskCode || 'Sync Detail'"></h2>
      </div>
      <button type="button" class="text-2xl text-gray-400 hover:text-gray-700 dark:hover:text-white" @click="open = false" aria-label="Close audit detail">×</button>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-400">Fetch Step</p><p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="detail.stepFailed === 'FETCH_GET' ? 'Failed' : 'Completed'"></p></div>
      <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-400">Insert Step</p><p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="detail.stepFailed === 'DB_INSERT' ? 'Failed' : 'Completed'"></p></div>
      <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-400">Records Read / Written</p><p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200"><span x-text="detail.recordsRead || 0"></span> / <span x-text="detail.recordsWritten || 0"></span></p></div>
      <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-400">Duration</p><p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="(detail.duration || '0.00') + 's'"></p></div>
    </div>
    <div class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800"><p class="text-xs text-gray-400">Error Message</p>
      <p class="mt-1 text-sm text-gray-700 dark:text-gray-200" x-text="detail.errorMessage || 'No error recorded.'"></p>
    </div>
  </div>
</div>
