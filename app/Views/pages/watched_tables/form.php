<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$value = static function (string $field, string $default = '') use ($table, $oldInput) {
    if ($oldInput !== [] && array_key_exists($field, $oldInput)) {
        return (string) $oldInput[$field];
    }

    return (string) ($table[$field] ?? $default);
};

$tableOptions = $allTables;
$currentTable = $value('table_name');
if ($currentTable !== '' && ! in_array($currentTable, $tableOptions, true)) {
    $tableOptions[] = $currentTable;
    sort($tableOptions);
}
?>
<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
  <?= view('components/breadcrumb', ['items' => [
      ['label' => 'Home', 'href' => base_url('/')],
      ['label' => 'Watched Tables', 'href' => base_url('watched-tables')],
      ['label' => $isEdit ? 'Edit Tabel' : 'Tambah Tabel'],
  ]]) ?>

  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-wide text-brand-500">MD-Bridge</p>
      <h1 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"><?= $isEdit ? 'Edit Watched Table' : 'Tambah Watched Table' ?></h1>
      <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Pilih tabel dari hasil introspeksi source aktif dan tentukan kolom penanda sinkron serta ambang stale-nya.</p>
    </div>
    <a href="<?= $cancelUrl ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Kembali
    </a>
  </div>

  <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <form method="post" action="<?= esc($formAction) ?>" class="p-5 md:p-6">
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

      <div class="grid gap-5 md:grid-cols-2">
        <div>
          <label for="source" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Database (filter introspeksi)</label>
          <select id="source" name="source" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90">
            <option value="">— Semua source —</option>
            <?php foreach ($sources as $sourceOption): ?>
              <option value="<?= esc($sourceOption['code']) ?>" <?= $selectedSource === $sourceOption['code'] ? 'selected' : '' ?>>
                <?= esc($sourceOption['label']) ?> (<?= esc($sourceOption['code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Menyaring daftar tabel & kolom agar sesuai database terpilih. Registrasi tetap berlaku untuk semua source aktif.</p>
        </div>

        <div>
          <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Label <span class="text-error-500">*</span></label>
          <input type="text" id="label" name="label" value="<?= esc($value('label')) ?>" required maxlength="150" placeholder="Material Master" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90" />
        </div>

        <div>
          <label for="table_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Tabel <span class="text-error-500">*</span></label>
          <select id="table_name" name="table_name" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90">
            <option value="">— pilih tabel —</option>
            <?php foreach ($tableOptions as $name): ?>
              <option value="<?= esc($name) ?>" <?= $currentTable === $name ? 'selected' : '' ?>><?= esc($name) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($allTables)): ?>
            <p class="mt-1.5 text-xs text-warning-600">Tidak ada source aktif yang bisa diintrospeksi — periksa konfigurasi koneksi.</p>
          <?php endif; ?>
        </div>

        <div>
          <label for="sync_column" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sync Column <span class="text-error-500">*</span></label>
          <select id="sync_column" name="sync_column" required class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90">
            <option value="">— pilih tabel dulu —</option>
          </select>
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kolom waktu penanda sinkron; opsi diisi otomatis dari kolom tabel terpilih.</p>
        </div>

        <div>
          <label for="stale_after_minutes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Stale After (menit) <span class="text-error-500">*</span></label>
          <input type="number" id="stale_after_minutes" name="stale_after_minutes" value="<?= esc($value('stale_after_minutes', '1440')) ?>" required min="1" max="525600" class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-transparent dark:text-white/90" />
          <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">1440 menit = 24 jam.</p>
        </div>
      </div>

      <div class="mt-5 flex items-center gap-2">
        <input type="checkbox" id="is_active" name="is_active" value="1" <?= $value('is_active', '1') === '1' ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20" />
        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Aktif (ikut check berikutnya)</label>
      </div>

      <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
          <?= $isEdit ? 'Simpan Perubahan' : 'Daftarkan Tabel' ?>
        </button>
        <a href="<?= $cancelUrl ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">Batal</a>
      </div>
    </form>
  </section>
</div>

<script>
(function () {
  var sourceSelect = document.getElementById('source');
  var tableSelect = document.getElementById('table_name');
  var columnSelect = document.getElementById('sync_column');
  var currentColumn = <?= json_encode($value('sync_column')) ?>;
  var currentTable = <?= json_encode($currentTable) ?>;
  var tablesUrl = <?= json_encode(base_url('watched-tables/tables')) ?>;
  var columnsUrl = <?= json_encode(base_url('watched-tables/columns')) ?>;

  function currentSource() {
    return sourceSelect.value;
  }

  function sourceQuery() {
    return '&source=' + encodeURIComponent(currentSource());
  }

  function renderColumns(columns) {
    columnSelect.innerHTML = '';

    if (!columns.length) {
      var empty = document.createElement('option');
      empty.value = '';
      empty.textContent = '— kolom tidak ditemukan —';
      columnSelect.appendChild(empty);
      return;
    }

    var preferred = currentColumn && columns.indexOf(currentColumn) !== -1
      ? currentColumn
      : (columns.indexOf('synced_at') !== -1 ? 'synced_at' : columns[0]);

    columns.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === preferred;
      columnSelect.appendChild(option);
    });
  }

  function renderTables(names) {
    var current = tableSelect.value || currentTable;

    // Tabel terpilih tetap ditampilkan meski tidak ada di lingkup source.
    if (current && names.indexOf(current) === -1) {
      names = names.concat([current]).sort();
    }

    tableSelect.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— pilih tabel —';
    tableSelect.appendChild(placeholder);

    names.forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      option.selected = name === current;
      tableSelect.appendChild(option);
    });
  }

  function loadColumns() {
    var table = tableSelect.value;
    currentColumn = columnSelect.value || currentColumn;

    if (!table) {
      renderColumns([]);
      return;
    }

    columnSelect.disabled = true;
    fetch(columnsUrl + '?table=' + encodeURIComponent(table) + sourceQuery(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json(); })
      .then(function (data) { renderColumns(data.columns || []); })
      .catch(function () { renderColumns([]); })
      .finally(function () { columnSelect.disabled = false; });
  }

  function loadTables() {
    tableSelect.disabled = true;
    fetch(tablesUrl + '?source=' + encodeURIComponent(currentSource()), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json(); })
      .then(function (data) { renderTables(data.tables || []); })
      .catch(function () { renderTables([]); })
      .finally(function () {
        tableSelect.disabled = false;
        loadColumns();
      });
  }

  sourceSelect.addEventListener('change', loadTables);
  tableSelect.addEventListener('change', loadColumns);

  if (tableSelect.value) {
    loadColumns();
  } else {
    renderColumns([]);
  }
})();
</script>
<?= $this->endSection() ?>
