<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400', 'dot' => 'bg-success-500'],
    0 => ['label' => 'Inactive', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'dot' => 'bg-gray-400'],
];
$flashSuccess = session()->getFlashdata('flash_success');
$flashError = session()->getFlashdata('flash_error');
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'API Task Registry'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90">API Task Registry</h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Kelola konfigurasi task sinkronisasi: endpoint sumber, tabel target, dan batch.</p>
    </div>
    <a href="<?= base_url('tasks/new') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
      <i class="fa-solid fa-plus" aria-hidden="true"></i>
      Tambah Task
    </a>
  </div>

  <?php if ($flashSuccess): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-success-200 bg-success-50 p-4 text-sm font-medium text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-300">
      <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
      <span><?= esc($flashSuccess) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-error-200 bg-error-50 p-4 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300">
      <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
      <span><?= esc($flashError) ?></span>
    </div>
  <?php endif; ?>

  <section class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1150px] text-left">
        <thead class="bg-brand-500 text-white">
          <tr>
            <th class="px-5 py-3 text-sm font-semibold">Task</th>
            <th class="px-5 py-3 text-sm font-semibold">Category</th>
            <th class="px-5 py-3 text-sm font-semibold">Source</th>
            <th class="px-5 py-3 text-sm font-semibold">Target Table</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Batch</th>
            <th class="px-5 py-3 text-sm font-semibold">Monitoring</th>
            <th class="px-5 py-3 text-sm font-semibold">Status</th>
            <th class="px-5 py-3 text-right text-sm font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          <?php if (empty($tasks)): ?>
            <tr>
              <td colspan="8" class="px-5 py-12 text-center">
                <i class="fa-solid fa-list-check text-2xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                <p class="mt-3 font-medium text-gray-700 dark:text-gray-200">Belum ada task terdaftar</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tambahkan task pertama dengan tombol "Tambah Task".</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tasks as $task): ?>
              <?php $isActive = (int) $task['is_active'] === 1; ?>
              <?php $status = $statusMap[(int) $task['is_active']] ?? $statusMap[0]; ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                <td class="px-5 py-4">
                  <p class="font-semibold text-gray-800 dark:text-white/90"><?= esc($task['task_name']) ?></p>
                  <code class="mt-0.5 block text-xs text-gray-400"><?= esc($task['task_code']) ?></code>
                </td>
                <td class="px-5 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 font-mono text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['category']) ?></span></td>
                <td class="max-w-[260px] px-5 py-4">
                  <span class="rounded-full bg-gray-100 px-2 py-0.5 font-mono text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['source_type']) ?></span>
                  <code class="mt-1 block truncate text-xs text-gray-500 dark:text-gray-400" title="<?= esc($task['source_endpoint'], 'attr') ?>"><?= esc($task['source_endpoint']) ?></code>
                </td>
                <td class="px-5 py-4"><code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?= esc($task['target_table']) ?></code></td>
                <td class="px-5 py-4 text-right font-mono text-sm text-gray-700 dark:text-gray-200"><?= number_format((int) $task['batch_size']) ?></td>
                <td class="px-5 py-4">
                  <?php if (!empty($task['monitoring_linked'])): ?>
                    <a href="<?= base_url('monitoring/' . rawurlencode($task['monitoring_app_code']) . '/' . rawurlencode($task['target_table'])) ?>" class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 transition hover:bg-success-100 dark:bg-success-500/15 dark:text-success-400" title="Buka di monitoring (<?= esc($task['monitoring_app_code']) ?>)"><span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>Linked</a>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Not Linked</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $status['class'] ?>"><span class="h-1.5 w-1.5 rounded-full <?= $status['dot'] ?>"></span><?= esc($status['label']) ?></span></td>
                <td class="px-5 py-4">
                  <div class="flex items-center justify-end gap-1.5">
                    <a
                      href="<?= base_url('tasks/edit/' . (int) $task['id']) ?>"
                      title="Edit task"
                      aria-label="Edit task <?= esc($task['task_name'], 'attr') ?>"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                    >
                      <i class="fa-solid fa-pen-to-square text-xs" aria-hidden="true"></i>
                    </a>

                    <form method="post" action="<?= base_url('tasks/toggle/' . (int) $task['id']) ?>" onsubmit="return confirm('<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> task ini?')">
                      <?= csrf_field() ?>
                      <button
                        type="submit"
                        title="<?= $isActive ? 'Nonaktifkan task' : 'Aktifkan task' ?>"
                        aria-label="<?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?> task <?= esc($task['task_name'], 'attr') ?>"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                      >
                        <i class="fa-solid <?= $isActive ? 'fa-toggle-on text-success-600' : 'fa-toggle-off' ?> text-xs" aria-hidden="true"></i>
                      </button>
                    </form>

                    <form method="post" action="<?= base_url('tasks/delete/' . (int) $task['id']) ?>" onsubmit="return confirm('Hapus task &quot;<?= esc($task['task_name'], 'attr') ?>&quot;? Tindakan ini tidak bisa dibatalkan.')">
                      <?= csrf_field() ?>
                      <button
                        type="submit"
                        title="Hapus task"
                        aria-label="Hapus task <?= esc($task['task_name'], 'attr') ?>"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-error-600 transition hover:border-error-300 hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:border-error-700 dark:hover:bg-error-500/10"
                      >
                        <i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?= $this->endSection() ?>
