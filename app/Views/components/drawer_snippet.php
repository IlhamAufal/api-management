<div
  x-data="{ open: false, snippet: {} }"
  x-on:open-snippet.window="snippet = $event.detail || {}; open = true"
  x-show="open"
  class="fixed inset-0 z-[99999] bg-gray-900/40"
  style="display: none;"
  @keydown.escape.window="open = false"
>
  <aside class="ml-auto flex h-full w-full max-w-xl flex-col bg-white p-6 shadow-theme-xl dark:bg-gray-900" @click.outside="open = false">
    <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-medium tracking-wide text-brand-500">Reusable Snippet</p><h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90" x-text="snippet.taskName || 'Sync task'"></h2></div><button type="button" class="text-2xl text-gray-400 hover:text-gray-700 dark:hover:text-white" @click="open = false" aria-label="Close snippet drawer">×</button></div>
    <div class="mt-6 space-y-5 overflow-y-auto">
      <div><div class="mb-2 flex items-center justify-between"><p class="text-sm font-semibold text-gray-700 dark:text-gray-200">HTTPie</p><button type="button" class="text-sm font-medium text-brand-500" @click="navigator.clipboard.writeText(snippet.httpie || '').then(() => window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'HTTPie snippet copied.' } }))).catch(() => window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: 'Clipboard access failed.' } })))">Copy</button></div><pre class="overflow-x-auto rounded-xl bg-gray-900 p-4 text-xs text-gray-100"><code x-text="snippet.httpie || ''"></code></pre></div>
      <div><div class="mb-2 flex items-center justify-between"><p class="text-sm font-semibold text-gray-700 dark:text-gray-200">SQL audit</p><button type="button" class="text-sm font-medium text-brand-500" @click="navigator.clipboard.writeText(snippet.sql || '').then(() => window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'SQL audit snippet copied.' } }))).catch(() => window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: 'Clipboard access failed.' } })))">Copy</button></div><pre class="overflow-x-auto rounded-xl bg-gray-900 p-4 text-xs text-gray-100"><code x-text="snippet.sql || ''"></code></pre></div>
    </div>
  </aside>
</div>
