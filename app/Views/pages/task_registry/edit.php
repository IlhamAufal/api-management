<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'API Task Registry', 'href' => base_url('tasks')],
      ['label' => 'Edit Task'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-wide text-brand-500">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">Edit Task</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
        Perbarui konfigurasi untuk <span class="font-semibold text-gray-700 dark:text-gray-200"><?= esc($task['task_name'] ?? '') ?></span>
        <code class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['task_code'] ?? '') ?></code>.
      </p>
    </div>
    <a href="<?= base_url('tasks') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Kembali ke Daftar
    </a>
  </div>

  <?= view('components/task_form', [
      'task'         => $task,
      'errors'       => $errors,
      'oldInput'     => $oldInput,
      'targetTables' => $targetTables,
      'categories'   => $categories,
      'sourceTypes'  => $sourceTypes,
      'formAction'   => $formAction,
      'isEdit'       => $isEdit,
      'cancelUrl'    => $cancelUrl,
  ]) ?>
</div>
<?= $this->endSection() ?>
