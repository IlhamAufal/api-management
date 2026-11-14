<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
// Nilai satu baris: dari old input (bila ada), lalu data tabel, lalu default.
$rowValue = static function (array $row, array $table, string $field, string $default = '') {
    if ($row !== [] && array_key_exists($field, $row)) {
        return (string) $row[$field];
    }

    return (string) ($table[$field] ?? $default);
};

// Baris awal: mode edit = 1 baris dari $table; mode tambah = baris dari old
// input (rows[]) atau 1 baris kosong.
$initialRows = [];
if ($isEdit) {
    $initialRows[] = $oldInput !== [] ? $oldInput : [];
} else {
    $oldRows = is_array($oldInput['rows'] ?? null) ? array_values($oldInput['rows']) : [];
    foreach ($oldRows as $oldRow) {
        if (is_array($oldRow)) {
            $oldRow['is_active'] = isset($oldRow['is_active']) ? '1' : '0';
            $initialRows[]       = $oldRow;
        }
    }
    if ($initialRows === []) {
        $initialRows[] = [];
    }
}

$tableOptions = $allTables;
foreach ($initialRows as $initialRow) {
    $name = (string) ($initialRow['table_name'] ?? ($isEdit ? ($table['table_name'] ?? '') : ''));
    if ($name !== '' && ! in_array($name, $tableOptions, true)) {
        $tableOptions[] = $name;
    }
}
sort($tableOptions);

/**
 * Render satu baris isian. $idx = indeks numerik atau '__IDX__' (template).
 * Mode edit memakai nama field datar; mode tambah memakai rows[idx][field].
 */
$renderRow = static function ($idx, array $row) use ($isEdit, $table, $tableOptions, $rowValue): string {
    $name = static fn (string $field): string => $isEdit ? $field : "rows[{$idx}][{$field}]";
    $id   = static fn (string $field): string => $isEdit ? $field : "{$field}_{$idx}";
    $val  = static fn (string $field, string $default = '') => $rowValue($row, $table ?? [], $field, $default);

    ob_start();
    ?>
    <div class="wt-row rounded-lg border border-gray-200/80 bg-gray-50/40 p-4 dark:border-gray-800 dark:bg-gray-800/30" data-current-column="<?= esc($val('sync_column'), 'attr') ?>">
      <?php if (! $isEdit): ?>
        <div class="mb-4 flex items-center justify-between">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Kolom #<span class="wt-row-number font-mono"></span></p>
          <button type="button" class="wt-remove inline-flex h-7 items-center gap-1.5 rounded-md border border-gray-200 px-2.5 text-xs font-medium text-error-600 transition-colors hover:bg-error-50 dark:border-gray-700 dark:text-error-400 dark:hover:bg-error-500/10" aria-label="Hapus kolom ini">
            <i class="fa-solid fa-trash text-[10px]" aria-hidden="true"></i>
            Hapus
          </button>
        </div>
      <?php endif; ?>

      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div>
          <label for="<?= $id('label') ?>" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Label <span class="text-error-500">*</span></label>
          <input type="text" id="<?= $id('label') ?>" name="<?= $name('label') ?>" value="<?= esc($val('label')) ?>" required maxlength="150" placeholder="Material Master" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90" />
        </div>

        <div>
          <?= view('components/custom_select', ['cs' => [
              'name'        => $name('table_name'),
              'id'          => $id('table_name'),
              'value'       => $val('table_name'),
              'options'     => $tableOptions,
              'placeholder' => '— pilih tabel —',
              'label'       => 'Nama Tabel <span class="text-error-500">*</span>',
              'required'    => true,
              'attributes'  => 'data-role="table"',
          ]]) ?>
        </div>

        <div>
          <?= view('components/custom_select', ['cs' => [
              'name'        => $name('sync_column'),
              'id'          => $id('sync_column'),
              'value'       => $val('sync_column'),
              'options'     => [],
              'placeholder' => '— pilih tabel dulu —',
              'label'       => 'Sync Column <span class="text-error-500">*</span>',
              'required'    => true,
              'attributes'  => 'data-role="column"',
          ]]) ?>
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kolom waktu penanda sinkron; opsi diisi otomatis dari kolom tabel terpilih.</p>
        </div>

        <div>
          <label for="<?= $id('stale_after_minutes') ?>" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Stale After (menit) <span class="text-error-500">*</span></label>
          <input type="number" id="<?= $id('stale_after_minutes') ?>" name="<?= $name('stale_after_minutes') ?>" value="<?= esc($val('stale_after_minutes', '1440')) ?>" required min="1" max="525600" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90" />
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">1440 menit = 24 jam.</p>
        </div>

        <div>
          <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Aktif</span>
          <label for="<?= $id('is_active') ?>" class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90">
            <input type="checkbox" id="<?= $id('is_active') ?>" name="<?= $name('is_active') ?>" value="1" <?= $val('is_active', '1') === '1' ? 'checked' : '' ?> class="peer sr-only" />
            <span class="toggle-track" aria-hidden="true"></span>
            Ikut check berikutnya
            <span class="toggle-state" data-on="Aktif" data-off="Nonaktif" aria-hidden="true"></span>
          </label>
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Nonaktifkan untuk mengecualikan tabel; riwayat tetap tersimpan.</p>
        </div>
      </div>
    </div>
    <?php

    return (string) ob_get_clean();
};
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 sm:p-6 lg:p-8">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Watched Tables', 'href' => base_url('watched-tables')],
      ['label' => $isEdit ? 'Edit Tabel' : 'Tambah Tabel'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= $isEdit ? 'Edit Watched Table' : 'Tambah Watched Table' ?></h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">Pilih tabel dari hasil introspeksi source aktif dan tentukan kolom penanda sinkron serta ambang stale-nya<?= $isEdit ? '' : ' (gunakan Tambah Kolom untuk mendaftarkan beberapa tabel sekaligus)' ?></p>
    </div>
    <a href="<?= $cancelUrl ?>" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">
      <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
      Kembali
    </a>
  </div>

  <section class="rounded-xl border border-gray-200/80 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900/60">
    <form method="post" action="<?= esc($formAction) ?>" class="p-5 sm:p-6">
      <?= csrf_field() ?>

      <?php if (! empty($errors)): ?>
        <div class="mb-5 flex items-start gap-2 rounded-xl border border-error-200 bg-error-50 p-4 text-sm font-medium text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-300">
          <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
          <div>
            <p class="font-semibold">Periksa kembali input Anda:</p>
            <ul class="mt-1 list-inside list-disc">
              <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <div class="mb-5 max-w-xl">
        <?php
        $sourceChoices = [];
        foreach ($sources as $sourceOption) {
            $sourceChoices[$sourceOption['code']] = $sourceOption['label'] . ' (' . $sourceOption['code'] . ')';
        }
        ?>
        <?= view('components/custom_select', ['cs' => [
            'name'        => 'source',
            'id'          => 'source',
            'value'       => $selectedSource,
            'options'     => $sourceChoices,
            'placeholder' => '— Semua source —',
            'label'       => 'Database (filter introspeksi)',
        ]]) ?>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Menyaring daftar tabel & kolom agar sesuai database terpilih. Registrasi tetap berlaku untuk semua source aktif.</p>
      </div>

      <div id="wt-rows" class="space-y-4">
        <?php foreach ($initialRows as $i => $initialRow): ?>
          <?= $renderRow($i, $initialRow) ?>
        <?php endforeach; ?>
      </div>

      <?php if (! $isEdit): ?>
        <template id="wt-row-template"><?= $renderRow('__IDX__', []) ?></template>

        <button type="button" id="wt-add-row" class="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 px-4 text-sm font-medium text-gray-700 transition-colors hover:border-gray-400 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
          <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
          Tambah Kolom
        </button>
      <?php endif; ?>

      <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-brand-500">
          <i class="fa-solid fa-floppy-disk text-xs" aria-hidden="true"></i>
          <?= $isEdit ? 'Simpan Perubahan' : 'Daftarkan Tabel' ?>
        </button>
        <a href="<?= $cancelUrl ?>" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300 dark:hover:bg-gray-800">Batal</a>
      </div>
    </form>
  </section>
</div>

<script>
(function () {
  var sourceSelect = document.getElementById('source');
  var rowsEl = document.getElementById('wt-rows');
  var template = document.getElementById('wt-row-template');
  var addButton = document.getElementById('wt-add-row');
  var tablesUrl = <?= json_encode(base_url('watched-tables/tables')) ?>;
  var columnsUrl = <?= json_encode(base_url('watched-tables/columns')) ?>;
  var knownTables = <?= json_encode(array_values($tableOptions)) ?>;
  var nextIndex = <?= (int) count($initialRows) ?>;

  function tableSelectOf(row) { return row.querySelector('select[data-role="table"]'); }
  function columnSelectOf(row) { return row.querySelector('select[data-role="column"]'); }

  function sourceQuery() {
    return '&source=' + encodeURIComponent(sourceSelect.value);
  }

  function renumber() {
    var rows = rowsEl.querySelectorAll('.wt-row');
    rows.forEach(function (row, i) {
      var num = row.querySelector('.wt-row-number');
      if (num) num.textContent = i + 1;
      var remove = row.querySelector('.wt-remove');
      if (remove) remove.hidden = rows.length === 1;
    });
  }

  function renderColumns(row, columns) {
    var select = columnSelectOf(row);
    select.innerHTML = '';

    if (!columns.length) {
      var empty = document.createElement('option');
      empty.value = '';
      empty.textContent = '— kolom tidak ditemukan —';
      select.appendChild(empty);
      return;
    }

    var current = row.dataset.currentColumn;
    var preferred = current && columns.indexOf(current) !== -1
      ? current
      : (columns.indexOf('synced_at') !== -1 ? 'synced_at' : columns[0]);

    columns.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === preferred;
      select.appendChild(option);
    });
  }

  function renderTables(row, names) {
    var select = tableSelectOf(row);
    var current = select.value;

    // Tabel terpilih tetap ditampilkan meski tidak ada di lingkup source.
    if (current && names.indexOf(current) === -1) {
      names = names.concat([current]).sort();
    }

    select.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— pilih tabel —';
    select.appendChild(placeholder);

    names.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === current;
      select.appendChild(option);
    });
  }

  function loadColumns(row) {
    var table = tableSelectOf(row).value;
    var select = columnSelectOf(row);
    row.dataset.currentColumn = select.value || row.dataset.currentColumn;

    if (!table) {
      renderColumns(row, []);
      return;
    }

    select.disabled = true;
    fetch(columnsUrl + '?table=' + encodeURIComponent(table) + sourceQuery(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json(); })
      .then(function (data) { renderColumns(row, data.columns || []); })
      .catch(function () { renderColumns(row, []); })
      .finally(function () { select.disabled = false; });
  }

  function loadTables() {
    var selects = Array.prototype.map.call(rowsEl.querySelectorAll('.wt-row'), tableSelectOf);
    selects.forEach(function (s) { s.disabled = true; });

    fetch(tablesUrl + '?source=' + encodeURIComponent(sourceSelect.value), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json(); })
      .then(function (data) { knownTables = data.tables || []; })
      .catch(function () { knownTables = []; })
      .finally(function () {
        rowsEl.querySelectorAll('.wt-row').forEach(function (row) {
          tableSelectOf(row).disabled = false;
          renderTables(row, knownTables);
          loadColumns(row);
        });
      });
  }

  function initRow(row) {
    tableSelectOf(row).addEventListener('change', function () { loadColumns(row); });

    var remove = row.querySelector('.wt-remove');
    if (remove) {
      remove.addEventListener('click', function () {
        if (rowsEl.querySelectorAll('.wt-row').length <= 1) return;
        row.remove();
        renumber();
      });
    }

    if (tableSelectOf(row).value) {
      loadColumns(row);
    } else {
      renderColumns(row, []);
    }
  }

  function addRow() {
    var holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replace(/__IDX__/g, String(nextIndex++));
    var row = holder.querySelector('.wt-row');
    rowsEl.appendChild(row);
    renderTables(row, knownTables);
    initRow(row);
    renumber();

    var label = row.querySelector('input[type="text"]');
    if (label) label.focus();
  }

  sourceSelect.addEventListener('change', loadTables);
  if (addButton) addButton.addEventListener('click', addRow);

  rowsEl.querySelectorAll('.wt-row').forEach(initRow);
  renumber();
})();
</script>
<?= $this->endSection() ?>
