<header
  x-data="{ showLogoutModal: false }"
  class="sticky top-0 z-40 flex w-full border-b border-gray-200 bg-white/95 backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/95"
>
  <div class="flex w-full items-center justify-between px-4 py-2.5 sm:px-6">
    <!-- Left: Sidebar Toggle & Minimal App Title -->
    <div class="flex items-center gap-3">
      <button
        :class="sidebarToggle ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800'"
        class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200/80 transition dark:border-gray-800"
        @click.stop="sidebarToggle = !sidebarToggle"
        aria-label="Toggle sidebar"
      >
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="4" y1="6" x2="20" y2="6"></line>
          <line x1="4" y1="12" x2="20" y2="12"></line>
          <line x1="4" y1="18" x2="20" y2="18"></line>
        </svg>
      </button>

      <a href="<?= base_url('/') ?>" class="flex items-center lg:hidden">
        <img class="h-7 w-auto dark:hidden" src="<?= base_url('assets/images/logo/logo.svg') ?>" alt="MD-Bridge" />
        <img class="hidden h-7 w-auto dark:block" src="<?= base_url('assets/images/logo/logo-dark.svg') ?>" alt="MD-Bridge" />
      </a>

      <div class="hidden items-center gap-2 lg:flex">
        <span class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white">MD-Bridge</span>
        <span class="text-xs text-gray-300 dark:text-gray-700">/</span>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Integration Monitor</span>
      </div>
    </div>

    <!-- Right: Minimalist Controls -->
    <div class="flex items-center gap-2 sm:gap-3">
      <!-- Status Indicator -->
      <div class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-gray-200/80 bg-gray-50/80 px-2.5 py-1 text-[11px] font-medium text-gray-600 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400">
        <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
        <span>Connected</span>
      </div>

      <div class="hidden md:block h-4 w-px bg-gray-200 dark:bg-gray-800"></div>

      <!-- User Profile -->
      <div class="flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
          <?= esc(strtoupper(substr(session()->get('auth_user_name') ?: 'U', 0, 1))) ?>
        </span>
        <div class="hidden sm:block text-left">
          <p class="max-w-[130px] truncate text-xs font-semibold text-gray-800 dark:text-gray-200 leading-none">
            <?= esc(session()->get('auth_user_name') ?: 'User') ?>
          </p>
          <p class="max-w-[130px] truncate text-[10px] text-gray-400 dark:text-gray-500 leading-tight mt-0.5">
            <?= esc(session()->get('auth_user_email') ?: '') ?>
          </p>
        </div>
      </div>

      <!-- Dark Mode Toggle (Clean SVG) -->
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click.prevent="darkMode = !darkMode"
        aria-label="Toggle dark mode"
        title="Toggle dark mode"
      >
        <svg x-show="darkMode" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
          <circle cx="12" cy="12" r="4"/>
          <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"/>
        </svg>
        <svg x-show="!darkMode" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
      </button>

      <!-- Logout Button (triggers confirmation modal) -->
      <button
        type="button"
        @click="showLogoutModal = true"
        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-red-600 px-3 text-xs font-semibold text-white shadow-xs transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:bg-red-600 dark:hover:bg-red-700"
        title="Logout"
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 5H5.75A1.75 1.75 0 0 0 4 6.75v10.5C4 18.22 4.78 19 5.75 19H9M15 8l4 4-4 4M8 12h11"/>
        </svg>
        <span class="hidden sm:inline">Logout</span>
      </button>
    </div>
  </div>

  <!-- Modal Konfirmasi Logout -->
  <div
    x-show="showLogoutModal"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/60 p-4"
    style="display: none;"
    @keydown.escape.window="showLogoutModal = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="logout-dialog-title"
  >
    <div
      class="w-full max-w-[380px] rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
      @click.outside="showLogoutModal = false"
    >
      <div class="flex items-start gap-3.5">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5H5.75A1.75 1.75 0 0 0 4 6.75v10.5C4 18.22 4.78 19 5.75 19H9M15 8l4 4-4 4M8 12h11"/>
          </svg>
        </div>
        <div class="flex-1">
          <h3 id="logout-dialog-title" class="text-sm font-semibold text-gray-900 dark:text-white">Konfirmasi Keluar</h3>
          <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
            Apakah Anda yakin ingin keluar dari sesi ini? Anda harus login kembali untuk mengakses sistem.
          </p>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-end gap-2.5">
        <button
          type="button"
          @click="showLogoutModal = false"
          class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          Batal
        </button>
        <form method="post" action="<?= base_url('logout') ?>" class="inline-flex m-0">
          <?= csrf_field() ?>
          <button
            type="submit"
            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-red-600 px-3.5 text-xs font-semibold text-white shadow-xs transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:bg-red-600 dark:hover:bg-red-700"
          >
            Ya, Keluar
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
