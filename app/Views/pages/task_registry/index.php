<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-white/[0.03]">
    <p class="text-sm font-semibold uppercase tracking-wide text-brand-500">MD-Bridge</p>
    <h1 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">API Task Registry</h1>
    <p class="mx-auto mt-3 max-w-md text-sm text-gray-500 dark:text-gray-400">Coming soon. Task management, configuration, and CRUD controls will appear here.</p>
    <div class="mx-auto mt-8 grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3">
      <?php for ($index = 0; $index < 3; $index++): ?>
        <div class="h-20 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
      <?php endfor; ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
