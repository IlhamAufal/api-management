<header
  x-data="{menuToggle: false}"
  class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900"
>
  <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
    <div class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800">
      <button
        :class="sidebarToggle ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 lg:h-11 lg:w-11 lg:border dark:border-gray-800 dark:text-gray-400"
        @click.stop="sidebarToggle = !sidebarToggle"
        aria-label="Toggle sidebar"
      >
        <svg class="fill-current" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 6.75A.75.75 0 0 1 4.75 6h14.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 6.75Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H4.75a.75.75 0 0 1-.75-.75Zm.75 4.5a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H4.75Z" fill="currentColor"/></svg>
      </button>
      <a href="<?= base_url('/') ?>" class="lg:hidden"><img class="h-8 dark:hidden" src="<?= base_url('assets/images/logo/logo.svg') ?>" alt="MD-Bridge" /><img class="hidden h-8 dark:block" src="<?= base_url('assets/images/logo/logo-dark.svg') ?>" alt="MD-Bridge" /></a>
      <div class="hidden lg:block">
        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">MD-Bridge Integration Monitor</p>
        <p class="text-xs text-gray-400">SAP Master Data Synchronization</p>
      </div>
      <button class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 lg:hidden" @click.stop="menuToggle = !menuToggle" aria-label="Toggle navigation"><span class="text-xl">•••</span></button>
    </div>

    <div :class="menuToggle ? 'flex' : 'hidden'" class="w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none">
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">
          <span class="h-2.5 w-2.5 rounded-full bg-success-500"></span>
          Connected
        </span>
        <button type="button" disabled class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white opacity-60 cursor-not-allowed" title="Sync All belum diaktifkan">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 11a8 8 0 0 0-14.9-4M4 13a8 8 0 0 0 14.9 4M5 3v4h4M19 21v-4h-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Sync All
        </button>
        <div class="hidden text-right sm:block">
          <p class="max-w-40 truncate text-xs font-semibold text-gray-700 dark:text-gray-200"><?= esc(session()->get('auth_user_name') ?: 'User') ?></p>
          <p class="max-w-40 truncate text-[11px] text-gray-400"><?= esc(session()->get('auth_user_email') ?: '') ?></p>
        </div>
        <form method="post" action="<?= base_url('logout') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-error-700 dark:hover:bg-error-500/10 dark:hover:text-error-400" title="Logout">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5H5.75A1.75 1.75 0 0 0 4 6.75v10.5C4 18.22 4.78 19 5.75 19H9M15 8l4 4-4 4M8 12h11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="hidden xl:inline">Logout</span>
          </button>
        </form>
        <button class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400" @click.prevent="darkMode = !darkMode" aria-label="Toggle dark mode"><span class="text-lg">◐</span></button>
      </div>
    </div>
  </div>
</header>
