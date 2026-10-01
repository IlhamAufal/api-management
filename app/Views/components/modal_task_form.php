<?php
/**
 * Create/Edit modal for API sync tasks.
 *
 * Expected variables:
 *   $task         array|null  — existing task when editing
 *   $openModal    bool        — render modal already opened
 *   $errors       array       — validation errors keyed by field
 *   $oldInput     array       — old submitted input keyed by field
 *   $targetTables array       — allowed target tables
 *   $categories   array       — allowed categories
 *   $sourceTypes  array       — allowed source types
 *   $formAction   string      — POST target URL
 *   $isEdit       bool
 */
$fieldValue = static function (string $field) use ($oldInput, $task) {
    if (array_key_exists($field, $oldInput)) {
        return (string) $oldInput[$field];
    }
    if ($task !== null && array_key_exists($field, $task)) {
        return (string) $task[$field];
    }
    return '';
};

$fieldError = static function (string $field) use ($errors) {
    return $errors[$field] ?? null;
};

$inputClass = 'h-11 w-full rounded-lg border px-3 text-sm text-gray-700 bg-white outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
?>
<div
  x-data="{ open: <?= $openModal ? 'true' : 'false' ?> }"
  x-show="open"
  x-transition.opacity
  class="fixed inset-0 z-[99999] flex items-start justify-center overflow-y-auto bg-gray-900/60 p-4 py-10"
  style="display: none;"
  @keydown.escape.window="open = false"
>
  <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-theme-xl dark:bg-gray-900" @click.outside="open = false">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-xs font-medium tracking-wide text-brand-500">API Task Registry</p>
        <h2 class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90"><?= $isEdit ? 'Edit Task' : 'Tambah Task Baru' ?></h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Konfigurasi task sinkronisasi tanpa menyentuh kode.</p>
      </div>
      <a href="<?= base_url('tasks') ?>" class="text-2xl leading-none text-gray-400 transition hover:text-gray-700 dark:hover:text-white" aria-label="Tutup form">&times;</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mt-4 rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300">
        Periksa kembali data yang diisi — ada <?= count($errors) ?> field yang belum valid.
      </div>
    <?php endif; ?>

    <form method="post" action="<?= esc($formAction) ?>" class="mt-5">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="task_code" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Task Code <span class="text-error-500">*</span></label>
          <input id="task_code" name="task_code" type="text" value="<?= esc($fieldValue('task_code')) ?>" placeholder="sap_material" class="<?= $inputClass ?> <?= $fieldError('task_code') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>" <?= $isEdit ? 'readonly' : '' ?> />
          <?php if ($error = $fieldError('task_code')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
          <p class="mt-1 text-xs text-gray-400">Huruf kecil, angka, dash/underscore. Tidak bisa diubah setelah dibuat.</p>
        </div>
        <div>
          <label for="task_name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Task Name <span class="text-error-500">*</span></label>
          <input id="task_name" name="task_name" type="text" value="<?= esc($fieldValue('task_name')) ?>" placeholder="Material Master Sync" class="<?= $inputClass ?> <?= $fieldError('task_name') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>" />
          <?php if ($error = $fieldError('task_name')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
        </div>
        <div>
          <label for="category" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
          <select id="category" name="category" class="<?= $inputClass ?> border-gray-300 dark:border-gray-700">
            <?php foreach ($categories as $category): ?>
              <option value="<?= esc($category) ?>" <?= $fieldValue('category') === $category ? 'selected' : '' ?>><?= esc($category) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="source_type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Source Type</label>
          <select id="source_type" name="source_type" class="<?= $inputClass ?> border-gray-300 dark:border-gray-700">
            <?php foreach ($sourceTypes as $sourceType): ?>
              <option value="<?= esc($sourceType) ?>" <?= $fieldValue('source_type') === $sourceType ? 'selected' : '' ?>><?= esc($sourceType) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label for="source_endpoint" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Source Endpoint <span class="text-error-500">*</span></label>
          <input id="source_endpoint" name="source_endpoint" type="text" value="<?= esc($fieldValue('source_endpoint')) ?>" placeholder="https://my433897-api.s4hana.cloud.sap/sap/opu/odata/sap/API_PRODUCT_SRV" class="<?= $inputClass ?> <?= $fieldError('source_endpoint') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>" />
          <?php if ($error = $fieldError('source_endpoint')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
          <p class="mt-1 text-xs text-gray-400">URL API atau koneksi database sumber.</p>
        </div>
        <div>
          <label for="target_table" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Target Table <span class="text-error-500">*</span></label>
          <select id="target_table" name="target_table" class="<?= $inputClass ?> <?= $fieldError('target_table') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>">
            <?php foreach ($targetTables as $targetTable): ?>
              <option value="<?= esc($targetTable) ?>" <?= $fieldValue('target_table') === $targetTable ? 'selected' : '' ?>><?= esc($targetTable) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($error = $fieldError('target_table')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
        </div>
        <div>
          <label for="batch_size" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Batch Size <span class="text-error-500">*</span></label>
          <input id="batch_size" name="batch_size" type="number" min="1" max="10000" value="<?= esc($fieldValue('batch_size') ?: '1500') ?>" class="<?= $inputClass ?> <?= $fieldError('batch_size') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>" />
          <?php if ($error = $fieldError('batch_size')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
        </div>
        <div>
          <label for="cron_expression" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Cron Expression</label>
          <input id="cron_expression" name="cron_expression" type="text" value="<?= esc($fieldValue('cron_expression')) ?>" placeholder="0 0 8 * *" class="<?= $inputClass ?> <?= $fieldError('cron_expression') ? 'border-error-400' : 'border-gray-300 dark:border-gray-700' ?>" />
          <?php if ($error = $fieldError('cron_expression')): ?><p class="mt-1 text-xs text-error-600 dark:text-error-400"><?= esc($error) ?></p><?php endif; ?>
          <p class="mt-1 text-xs text-gray-400">Kosongkan bila dijalankan manual saja.</p>
        </div>
        <div class="flex items-end">
          <label class="inline-flex cursor-pointer items-center gap-2.5">
            <input type="checkbox" name="is_active" value="1" <?= $task === null || $fieldValue('is_active') === '1' ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/30 dark:border-gray-700" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Task Aktif</span>
          </label>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
        <a href="<?= base_url('tasks') ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Batal</a>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Task' ?></button>
      </div>
    </form>
  </div>
</div>
