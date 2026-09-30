<div
  x-data="{
    show: false,
    type: 'success',
    message: '',
    timeout: null,
    notify(detail) {
      clearTimeout(this.timeout);
      this.type = detail.type === 'error' ? 'error' : 'success';
      this.message = detail.message || '';
      this.show = true;
      this.timeout = setTimeout(() => this.show = false, 4000);
    }
  }"
  x-on:notify.window="notify($event.detail || {})"
  x-show="show"
  x-transition:enter="transition ease-out duration-300"
  x-transition:enter-start="translate-x-8 opacity-0"
  x-transition:enter-end="translate-x-0 opacity-100"
  x-transition:leave="transition ease-in duration-200"
  x-transition:leave-start="translate-x-0 opacity-100"
  x-transition:leave-end="translate-x-8 opacity-0"
  class="fixed right-5 bottom-5 z-[100000] w-[calc(100%-2.5rem)] max-w-md"
  style="display: none;"
>
  <div
    :class="type === 'error' ? 'border-error-200 bg-error-50 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300' : 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-300'"
    class="flex items-start gap-3 rounded-xl border p-4 shadow-theme-lg"
    role="status"
    aria-live="polite"
  >
    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full" :class="type === 'error' ? 'bg-error-500 text-white' : 'bg-success-500 text-white'">
      <span x-text="type === 'error' ? '!' : '✓'"></span>
    </span>
    <p class="flex-1 text-sm font-medium" x-text="message"></p>
    <button type="button" class="text-current opacity-70 hover:opacity-100" @click="show = false" aria-label="Close notification">×</button>
  </div>
</div>
